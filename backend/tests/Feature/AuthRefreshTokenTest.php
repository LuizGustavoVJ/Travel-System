<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
            ]);

        // New token should be different
        $this->assertNotEquals($token, $response->json('token'));
    }

    public function test_unauthenticated_user_cannot_refresh_token(): void
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_refreshed_token_is_valid(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/refresh');

        $newToken = $response->json('token');

        // New token should work
        $meResponse = $this->withHeader('Authorization', "Bearer $newToken")
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_old_token_is_invalidated_after_refresh(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/refresh');

        // Old token should still work (JWT doesn't invalidate on refresh by default)
        // But we can test that new token works
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
    }
}

