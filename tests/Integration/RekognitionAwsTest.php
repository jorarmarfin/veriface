<?php

namespace Tests\Integration;

use App\Services\RekognitionService;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Llama a AWS REAL (crea/borra colecciones, genera costo).
 * Solo corre con: RUN_AWS_TESTS=1 php artisan test --group=aws
 */
#[Group('aws')]
class RekognitionAwsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!env('RUN_AWS_TESTS')) {
            $this->markTestSkipped('Tests de AWS desactivados (usa RUN_AWS_TESTS=1).');
        }
    }

    public function test_collection_lifecycle(): void
    {
        $rekognition = app(RekognitionService::class);
        $collectionId = 'test-collection-' . time();

        $created = $rekognition->createCollection($collectionId, 'Test');
        $this->assertTrue($created['success']);

        $described = $rekognition->describeCollection($collectionId);
        $this->assertTrue($described['success']);

        $listed = $rekognition->listCollections(maxResults: 50);
        $this->assertTrue($listed['success']);

        $deleted = $rekognition->deleteCollection($collectionId);
        $this->assertTrue($deleted['success']);
    }
}
