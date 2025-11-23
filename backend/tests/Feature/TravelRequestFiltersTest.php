<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    public function test_user_can_filter_travel_requests_by_status(): void
    {
        [$user, $token] = $this->authenticatedUser();

        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests?status=approved');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'approved');
    }

    public function test_user_can_filter_travel_requests_by_destination(): void
    {
        [$user, $token] = $this->authenticatedUser();

        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'São Paulo',
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'Rio de Janeiro',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests?destination=São Paulo');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.destination', 'São Paulo');
    }

    public function test_user_can_filter_travel_requests_by_date_range(): void
    {
        [$user, $token] = $this->authenticatedUser();

        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10),
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(20),
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(30),
        ]);

        $fromDate = now()->addDays(15)->toDateString();
        $toDate = now()->addDays(25)->toDateString();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/travel-requests?start_date_from={$fromDate}&start_date_to={$toDate}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_see_all_travel_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TravelRequest::factory()->count(2)->create(['user_id' => $user1->id]);
        TravelRequest::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_view_any_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);

        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $travelRequest->id,
                ],
            ]);
    }

    public function test_regular_user_only_sees_their_own_travel_requests(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $otherUser = User::factory()->create();

        TravelRequest::factory()->count(2)->create(['user_id' => $user->id]);
        TravelRequest::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_pagination_works_correctly(): void
    {
        [$user, $token] = $this->authenticatedUser();

        TravelRequest::factory()->count(15)->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/travel-requests?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }
}

