<?php

namespace Tests\Unit;

use App\Events\TravelRequestCreated;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestCreatedEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_travel_request_created_event_contains_travel_request(): void
    {
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'São Paulo, Brasil',
        ]);

        $event = new TravelRequestCreated($travelRequest);

        $this->assertInstanceOf(TravelRequest::class, $event->travelRequest);
        $this->assertEquals('São Paulo, Brasil', $event->travelRequest->destination);
        $this->assertEquals($user->id, $event->travelRequest->user_id);
    }

    public function test_travel_request_created_event_is_serializable(): void
    {
        $travelRequest = TravelRequest::factory()->create();

        $event = new TravelRequestCreated($travelRequest);

        // Test that the event can be serialized (important for queue)
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(TravelRequestCreated::class, $unserialized);
        $this->assertEquals($travelRequest->id, $unserialized->travelRequest->id);
        $this->assertEquals($travelRequest->destination, $unserialized->travelRequest->destination);
    }
}

