<?php

namespace Tests\Unit;

use App\Models\TravelRequest;
use App\Models\User;
use App\Repositories\TravelRequestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TravelRequestRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TravelRequestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TravelRequestRepository();
    }

    public function test_create_travel_request(): void
    {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'requester_name' => $user->name,
            'destination' => 'São Paulo',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'status' => 'requested',
        ];

        $travelRequest = $this->repository->create($data);

        $this->assertInstanceOf(TravelRequest::class, $travelRequest);
        $this->assertEquals($user->id, $travelRequest->user_id);
        $this->assertEquals('São Paulo', $travelRequest->destination);
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'destination' => 'São Paulo',
        ]);
    }

    public function test_find_by_id_returns_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create();

        $result = $this->repository->findById($travelRequest->id);

        $this->assertInstanceOf(TravelRequest::class, $result);
        $this->assertEquals($travelRequest->id, $result->id);
        $this->assertNotNull($result->user); // Eager loaded
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findById('non-existent-id');

        $this->assertNull($result);
    }

    public function test_update_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'destination' => 'Old Destination',
        ]);

        $updated = $this->repository->update($travelRequest, [
            'destination' => 'New Destination',
        ]);

        $this->assertEquals('New Destination', $updated->destination);
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'destination' => 'New Destination',
        ]);
    }

    public function test_delete_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create();

        $result = $this->repository->delete($travelRequest);

        $this->assertTrue($result);
        $this->assertSoftDeleted('travel_requests', [
            'id' => $travelRequest->id,
        ]);
    }

    public function test_get_all_for_user_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        TravelRequest::factory()->count(5)->create(['user_id' => $user->id]);
        TravelRequest::factory()->count(3)->create(); // Other user's requests

        $result = $this->repository->getAllForUser($user->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
    }

    public function test_get_all_returns_all_requests(): void
    {
        TravelRequest::factory()->count(5)->create();

        $result = $this->repository->getAll();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
    }

    public function test_approve_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $approved = $this->repository->approve($travelRequest, $admin->id);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($admin->id, $approved->approved_by);
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_cancel_travel_request(): void
    {
        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);
        $reason = 'Budget constraints';

        $cancelled = $this->repository->cancel($travelRequest, $admin->id, $reason);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals($admin->id, $cancelled->cancelled_by);
        $this->assertEquals($reason, $cancelled->cancelled_reason);
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'cancelled',
            'cancelled_by' => $admin->id,
            'cancelled_reason' => $reason,
        ]);
    }

    public function test_filter_by_status(): void
    {
        $user = User::factory()->create();
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $result = $this->repository->getAllForUser($user->id, ['status' => 'approved']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('approved', $result->first()->status);
    }

    public function test_filter_by_destination(): void
    {
        $user = User::factory()->create();
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'São Paulo',
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'Rio de Janeiro',
        ]);

        $result = $this->repository->getAllForUser($user->id, ['destination' => 'São Paulo']);

        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('São Paulo', $result->first()->destination);
    }

    public function test_filter_by_date_range(): void
    {
        $user = User::factory()->create();
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10),
        ]);
        TravelRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(20),
        ]);

        $result = $this->repository->getAllForUser($user->id, [
            'start_date' => now()->addDays(15)->toDateString(),
        ]);

        $this->assertEquals(1, $result->total());
    }
}

