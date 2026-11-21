<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.admin_emails' => ['admin@example.com']]);
    }

    private function user(string $email): User
    {
        return User::factory()->create(['email' => $email]);
    }

    public function test_guest_is_redirected_from_logs(): void
    {
        $this->get('/system/logs')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_view_logs_or_export(): void
    {
        $user = $this->user('otro@example.com');

        $this->actingAs($user)->get('/system/logs')->assertForbidden();
        $this->actingAs($user)->get('/export/validation-logs?institution_id=1')->assertForbidden();
    }

    public function test_admin_can_view_logs(): void
    {
        $this->actingAs($this->user('admin@example.com'))->get('/system/logs')->assertOk();
    }

    public function test_admin_email_match_is_case_insensitive(): void
    {
        $this->assertTrue($this->user('Admin@Example.com')->isAdmin());
    }

    public function test_nobody_is_admin_when_list_is_empty(): void
    {
        config(['services.admin_emails' => []]);

        $this->assertFalse($this->user('admin@example.com')->isAdmin());
    }

    public function test_public_logs_route_is_gone(): void
    {
        $this->get('/logs')->assertNotFound();
    }
}
