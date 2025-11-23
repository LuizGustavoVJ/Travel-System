<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    public function test_authenticated_user_can_create_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Business meeting',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'destination', 'start_date', 'end_date', 'status'],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'user_id' => $user->id,
            'destination' => 'São Paulo',
            'status' => 'requested',
        ]);
    }

    public function test_authenticated_user_can_list_their_travel_requests(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        TravelRequest::factory()->count(3)->create(['user_id' => $user->id]);
        TravelRequest::factory()->count(2)->create(); // Other user's requests

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_view_their_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        $travelRequest = TravelRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                    'destination' => $travelRequest->destination,
                ],
            ]);
    }

    public function test_authenticated_user_cannot_view_other_users_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        $otherUserRequest = TravelRequest::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/travel-requests/{$otherUserRequest->id}");

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_update_their_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        $travelRequest = TravelRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'destination' => 'Rio de Janeiro',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'destination' => 'Rio de Janeiro',
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'destination' => 'Rio de Janeiro',
        ]);
    }

    public function test_authenticated_user_cannot_update_other_users_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        $otherUserRequest = TravelRequest::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$otherUserRequest->id}", [
                'destination' => 'Rio de Janeiro',
            ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_delete_their_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        
        $travelRequest = TravelRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('travel_requests', [
            'id' => $travelRequest->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_travel_requests(): void
    {
        $response = $this->getJson('/api/travel-requests');

        $response->assertStatus(401);
    }

    public function test_travel_request_validation_works(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => '',
                'start_date' => 'invalid-date',
                'end_date' => now()->subDays(1)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination', 'start_date', 'end_date']);
    }
}
