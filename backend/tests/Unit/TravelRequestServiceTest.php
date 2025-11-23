<?php

namespace Tests\Unit;

use App\Events\TravelRequestApproved;
use App\Events\TravelRequestCancelled;
use App\Events\TravelRequestCreated;
use App\Models\TravelRequest;
use App\Models\User;
use App\Repositories\TravelRequestRepository;
use App\Services\TravelRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TravelRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private TravelRequestService $service;
    private TravelRequestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TravelRequestRepository();
        $this->service = new TravelRequestService($this->repository);
    }

    public function test_create_travel_request_dispatches_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $data = [
            'destination' => 'São Paulo',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'notes' => 'Business meeting',
        ];

        $travelRequest = $this->service->create($user, $data);

        $this->assertInstanceOf(TravelRequest::class, $travelRequest);
        $this->assertEquals($user->id, $travelRequest->user_id);
        $this->assertEquals('requested', $travelRequest->status);
        $this->assertEquals($user->name, $travelRequest->requester_name);

        Event::assertDispatched(TravelRequestCreated::class);
    }

    public function test_approve_travel_request_dispatches_event(): void
    {
        Event::fake();

        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $approved = $this->service->approve($travelRequest, $admin);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($admin->id, $approved->approved_by);

        Event::assertDispatched(TravelRequestApproved::class);
    }

    public function test_cancel_travel_request_dispatches_event(): void
    {
        Event::fake();

        $admin = User::factory()->admin()->create();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);
        $reason = 'Budget constraints';

        $cancelled = $this->service->cancel($travelRequest, $admin, $reason);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals($admin->id, $cancelled->cancelled_by);
        $this->assertEquals($reason, $cancelled->cancelled_reason);

        Event::assertDispatched(TravelRequestCancelled::class);
    }

    public function test_get_all_for_user_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        TravelRequest::factory()->count(5)->create(['user_id' => $user->id]);

        $result = $this->service->getAllForUser($user);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
    }

    public function test_get_all_for_admin_returns_all_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TravelRequest::factory()->count(3)->create(['user_id' => $user1->id]);
        TravelRequest::factory()->count(2)->create(['user_id' => $user2->id]);

        $result = $this->service->getAllForUser($admin);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
    }

    public function test_update_removes_restricted_fields(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $data = [
            'destination' => 'New Destination',
            'user_id' => 999, // Should be ignored
            'status' => 'approved', // Should be ignored
            'approved_by' => 999, // Should be ignored
        ];

        $updated = $this->service->update($travelRequest, $data);

        $this->assertEquals('New Destination', $updated->destination);
        $this->assertEquals($user->id, $updated->user_id); // Original user_id preserved
        $this->assertEquals('requested', $updated->status); // Original status preserved
    }

    public function test_get_by_id_returns_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create();

        $result = $this->service->getById($travelRequest->id);

        $this->assertInstanceOf(TravelRequest::class, $result);
        $this->assertEquals($travelRequest->id, $result->id);
    }

    public function test_get_by_id_returns_null_when_not_found(): void
    {
        $result = $this->service->getById('non-existent-id');

        $this->assertNull($result);
    }

    public function test_update_throws_validation_exception_when_end_date_before_start_date_from_database(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $data = [
            'end_date' => now()->addDays(5)->toDateString(), // Antes do start_date do banco
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->service->update($travelRequest, $data);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('end_date', $errors);
            $this->assertContains('A data de volta deve ser posterior à data de ida.', $errors['end_date']);
            throw $e;
        }
    }

    public function test_update_throws_validation_exception_when_end_date_before_provided_start_date(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $data = [
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(), // Antes do start_date fornecido
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        try {
            $this->service->update($travelRequest, $data);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('end_date', $errors);
            $this->assertContains('A data de volta deve ser posterior à data de ida.', $errors['end_date']);
            throw $e;
        }
    }

    public function test_update_succeeds_when_end_date_after_start_date_from_database(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $data = [
            'end_date' => now()->addDays(20)->toDateString(), // Depois do start_date do banco
        ];

        $updated = $this->service->update($travelRequest, $data);

        $this->assertEquals(now()->addDays(20)->toDateString(), $updated->end_date->toDateString());
    }

    public function test_update_succeeds_when_both_dates_are_valid(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $data = [
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(), // Depois do start_date fornecido
        ];

        $updated = $this->service->update($travelRequest, $data);

        $this->assertEquals(now()->addDays(20)->toDateString(), $updated->start_date->toDateString());
        $this->assertEquals(now()->addDays(25)->toDateString(), $updated->end_date->toDateString());
    }
}

