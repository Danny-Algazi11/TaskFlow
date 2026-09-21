<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_user_logout_flow(): void
    {
        // Sanctum only starts a session for requests whose Origin matches
        // SANCTUM_STATEFUL_DOMAINS — without it, /api/user and /api/logout
        // can't see the session at all. This mirrors the real SPA (and the
        // Referer header the context file says Postman needs).
        $this->withHeader('Origin', 'http://localhost:5173');

        $registerResponse = $this->postJson('/api/register', [
            'name' => 'Laila',
            'email' => 'laila@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $registerResponse->assertOk()->assertJsonPath('email', 'laila@example.com');

        $this->postJson('/api/logout')->assertNoContent();

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'laila@example.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertOk()->assertJsonPath('email', 'laila@example.com');

        $badLogin = $this->postJson('/api/login', [
            'email' => 'laila@example.com',
            'password' => 'wrong-password',
        ]);
        $badLogin->assertStatus(401);

        $this->postJson('/api/login', [
            'email' => 'laila@example.com',
            'password' => 'password123',
        ]);
        $this->getJson('/api/user')->assertOk()->assertJsonPath('email', 'laila@example.com');
    }
}
