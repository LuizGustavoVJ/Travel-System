<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\TravelRequestApproved;
use App\Events\TravelRequestCancelled;
use Tests\TestCase;

class TravelRequestBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    private function authenticatedAdmin(): array
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);
        return [$admin, $token];
    }

    public function test_admin_can_approve_requested_travel_request(): void
    {
        Event::fake();
        
        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Travel request approved successfully',
                'data' => [
                    'status' => 'approved',
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        Event::assertDispatched(TravelRequestApproved::class);
    }

    public function test_regular_user_cannot_approve_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Only admins can approve travel requests.',
            ]);
    }

    public function test_admin_cannot_approve_already_approved_travel_request(): void
    {
        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_non_approved_travel_request(): void
    {
        Event::fake();
        
        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Budget constraints',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Travel request cancelled successfully',
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'cancelled',
            'cancelled_by' => $admin->id,
            'cancelled_reason' => 'Budget constraints',
        ]);

        Event::assertDispatched(TravelRequestCancelled::class);
    }

    public function test_admin_cannot_cancel_approved_travel_request(): void
    {
        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized. Cannot cancel an approved travel request.',
            ]);
    }

    public function test_user_can_cancel_their_own_non_approved_travel_request(): void
    {
        Event::fake();
        
        [$user, $token] = $this->authenticatedUser();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Personal reasons',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Travel request cancelled successfully',
            ]);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'cancelled',
            'cancelled_by' => $user->id,
        ]);

        Event::assertDispatched(TravelRequestCancelled::class);
    }

    public function test_user_cannot_cancel_their_own_approved_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_other_users_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $otherUserRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$otherUserRequest->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_status_directly(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'status' => 'approved', // Trying to approve directly
            ]);

        // Status should not change
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'requested', // Still requested
        ]);
    }
}
