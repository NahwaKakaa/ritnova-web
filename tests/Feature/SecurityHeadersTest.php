<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesParticipants;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use CreatesParticipants, RefreshDatabase;

    public function test_security_headers_present(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');

        $response = $this->actingAs($this->participant('intervention'))->get('/beranda')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
