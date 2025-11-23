<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestNotFoundTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    public function test_returns_404_when_travel_request_not_found_on_show(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests/non-existent-id');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Travel request not found',
            ]);
    }

    public function test_returns_404_when_travel_request_not_found_on_update(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson('/api/travel-requests/non-existent-id', [
                'destination' => 'New Destination',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Travel request not found',
            ]);
    }

    public function test_returns_404_when_travel_request_not_found_on_delete(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/travel-requests/non-existent-id');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Travel request not found',
            ]);
    }

    public function test_returns_404_when_travel_request_not_found_on_approve(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests/non-existent-id/approve');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Travel request not found',
            ]);
    }

    public function test_returns_404_when_travel_request_not_found_on_cancel(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests/non-existent-id/cancel');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Travel request not found',
            ]);
    }
}

