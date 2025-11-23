<?php

namespace Tests\Unit;

use App\Models\TravelRequest;
use App\Models\User;
use App\Policies\TravelRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TravelRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TravelRequestPolicy();
    }

    public function test_any_authenticated_user_can_view_any_travel_requests(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_can_view_their_own_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $travelRequest));
    }

    public function test_user_cannot_view_other_users_travel_request(): void
    {
        $user = User::factory()->create();
        $otherUserRequest = TravelRequest::factory()->create();

        $this->assertFalse($this->policy->view($user, $otherUserRequest));
    }

    public function test_admin_can_view_any_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create();

        $this->assertTrue($this->policy->view($admin, $travelRequest));
    }

    public function test_any_authenticated_user_can_create_travel_requests(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_can_update_their_own_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $this->assertTrue($this->policy->update($user, $travelRequest));
    }

    public function test_user_cannot_update_other_users_travel_request(): void
    {
        $user = User::factory()->create();
        $otherUserRequest = TravelRequest::factory()->create();

        $this->assertFalse($this->policy->update($user, $otherUserRequest));
    }

    public function test_user_cannot_update_approved_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->assertFalse($this->policy->update($user, $travelRequest));
    }

    public function test_user_cannot_update_cancelled_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->assertFalse($this->policy->update($user, $travelRequest));
    }

    public function test_user_can_delete_their_own_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $this->assertTrue($this->policy->delete($user, $travelRequest));
    }

    public function test_user_cannot_delete_other_users_travel_request(): void
    {
        $user = User::factory()->create();
        $otherUserRequest = TravelRequest::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUserRequest));
    }

    public function test_user_cannot_delete_approved_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->assertFalse($this->policy->delete($user, $travelRequest));
    }

    public function test_user_cannot_delete_cancelled_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->assertFalse($this->policy->delete($user, $travelRequest));
    }

    public function test_admin_can_approve_requested_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $this->assertTrue($this->policy->approve($admin, $travelRequest));
    }

    public function test_admin_cannot_approve_already_approved_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        $this->assertFalse($this->policy->approve($admin, $travelRequest));
    }

    public function test_regular_user_cannot_approve_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $this->assertFalse($this->policy->approve($user, $travelRequest));
    }

    public function test_admin_can_cancel_non_approved_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $this->assertTrue($this->policy->cancel($admin, $travelRequest));
    }

    public function test_admin_cannot_cancel_approved_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        $this->assertFalse($this->policy->cancel($admin, $travelRequest));
    }

    public function test_user_can_cancel_their_own_non_approved_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $this->assertTrue($this->policy->cancel($user, $travelRequest));
    }

    public function test_user_cannot_cancel_their_own_approved_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->assertFalse($this->policy->cancel($user, $travelRequest));
    }

    public function test_user_cannot_cancel_other_users_travel_request(): void
    {
        $user = User::factory()->create();
        $otherUserRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $this->assertFalse($this->policy->cancel($user, $otherUserRequest));
    }
}
