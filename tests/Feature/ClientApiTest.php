<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\People;
use App\Models\RekognitionCollection;
use App\Services\RekognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    private function institution(array $attrs = []): array
    {
        $collection = RekognitionCollection::firstOrCreate(
            ['collection_id' => 'test-collection'],
            ['name' => 'test', 'is_active' => true]
        );

        $institution = Institution::create(array_merge([
            'name' => 'Inst',
            'event' => 'Evento',
            'filepath' => 'inst',
            'rekognition_collection_id' => $collection->id,
            'is_active' => true,
            'validations_contracted' => 2,
        ], $attrs));

        return [$institution, $institution->generateApiKey()];
    }

    private function headers(string $key): array
    {
        return ['X-Institution-Key' => $key, 'Accept' => 'application/json'];
    }

    public function test_rejects_missing_or_wrong_key(): void
    {
        [$institution] = $this->institution();

        $this->getJson("/api/v1/client/{$institution->uuid}/status")->assertStatus(401);
        $this->getJson("/api/v1/client/{$institution->uuid}/status", $this->headers('vf_wrong'))->assertStatus(401);
        $this->getJson('/api/v1/client/no-existe/status', $this->headers('vf_wrong'))->assertStatus(401);
    }

    public function test_key_is_stored_hashed_and_old_key_dies_on_regenerate(): void
    {
        [$institution, $key] = $this->institution();

        $this->assertNotSame($key, $institution->fresh()->api_key_hash);

        $new = $institution->generateApiKey();

        $this->getJson("/api/v1/client/{$institution->uuid}/status", $this->headers($key))->assertStatus(401);
        $this->getJson("/api/v1/client/{$institution->uuid}/status", $this->headers($new))->assertOk();
    }

    public function test_key_of_other_institution_is_rejected(): void
    {
        [$a] = $this->institution();
        [, $keyB] = $this->institution(['name' => 'Otra', 'filepath' => 'otra']);

        $this->getJson("/api/v1/client/{$a->uuid}/status", $this->headers($keyB))->assertStatus(401);
    }

    public function test_status_reports_quota(): void
    {
        [$institution, $key] = $this->institution(['validations_contracted' => 10, 'validations_used' => 4]);

        $this->getJson("/api/v1/client/{$institution->uuid}/status", $this->headers($key))
            ->assertOk()
            ->assertJsonPath('data.validations_contracted', 10)
            ->assertJsonPath('data.validations_used', 4)
            ->assertJsonPath('data.validations_remaining', 6)
            ->assertJsonPath('data.quota_exceeded', false);
    }

    public function test_analyze_returns_403_when_quota_exceeded(): void
    {
        [$institution, $key] = $this->institution(['validations_contracted' => 1, 'validations_used' => 1]);

        $this->postJson("/api/v1/client/{$institution->uuid}/analyze", ['image' => 'abc'], $this->headers($key))
            ->assertStatus(403)
            ->assertJsonPath('type', 'quota_exceeded');
    }

    public function test_analyze_matches_person_and_consumes_quota(): void
    {
        [$institution, $key] = $this->institution();
        People::create([
            'institution_id' => $institution->id,
            'names' => 'Juan Perez',
            'document_number' => '12345678',
        ]);

        $mock = Mockery::mock(RekognitionService::class);
        $mock->shouldReceive('searchFacesByImage')->once()->andReturn([
            'success' => true,
            'matches' => [['similarity' => 99.1, 'face_id' => 'f1', 'external_image_id' => '12345678.jpg']],
        ]);
        $this->app->instance(RekognitionService::class, $mock);

        $this->postJson("/api/v1/client/{$institution->uuid}/analyze", ['image' => 'data:image/jpeg;base64,AAAA'], $this->headers($key))
            ->assertOk()
            ->assertJsonPath('type', 'match')
            ->assertJsonPath('data.names', 'Juan Perez');

        $this->assertSame(1, $institution->fresh()->validations_used);
        $this->assertDatabaseHas('validation_logs', ['institution_id' => $institution->id, 'matched' => true]);
    }

    public function test_aws_failure_refunds_quota(): void
    {
        [$institution, $key] = $this->institution();

        $mock = Mockery::mock(RekognitionService::class);
        $mock->shouldReceive('searchFacesByImage')->twice()
            ->andReturnUsing(
                fn () => ['success' => false, 'message' => 'AWS error'],
                fn () => throw new \RuntimeException('boom'),
            );
        $this->app->instance(RekognitionService::class, $mock);

        $url = "/api/v1/client/{$institution->uuid}/analyze";

        $this->postJson($url, ['image' => 'AAAA'], $this->headers($key))->assertStatus(400);
        $this->assertSame(0, $institution->fresh()->validations_used);

        $this->postJson($url, ['image' => 'AAAA'], $this->headers($key))->assertStatus(500);
        $this->assertSame(0, $institution->fresh()->validations_used);
    }
}
