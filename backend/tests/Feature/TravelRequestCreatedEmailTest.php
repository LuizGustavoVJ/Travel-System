<?php

namespace Tests\Feature;

use App\Events\TravelRequestCreated;
use App\Mail\TravelRequestCreatedMail;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TravelRequestCreatedEmailTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    public function test_created_email_is_sent_when_travel_request_is_created(): void
    {
        Mail::fake();

        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo, Brasil',
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Business meeting',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'destination', 'start_date', 'end_date', 'status'],
            ]);

        // Assert welcome email was queued
        // TravelRequestCreatedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCreatedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_travel_request_created_event_is_dispatched_when_travel_request_is_created(): void
    {
        Event::fake();

        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'Rio de Janeiro, Brasil',
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Conference',
            ]);

        $response->assertStatus(201);

        // Assert event was dispatched
        Event::assertDispatched(TravelRequestCreated::class, function ($event) use ($user) {
            return $event->travelRequest->user_id === $user->id &&
                   $event->travelRequest->destination === 'Rio de Janeiro, Brasil';
        });
    }

    public function test_created_email_contains_correct_travel_request_information(): void
    {
        Mail::fake();

        [$user, $token] = $this->authenticatedUser();

        $travelRequestData = [
            'destination' => 'Belo Horizonte, Brasil',
            'start_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'notes' => 'Training session',
        ];

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', $travelRequestData);

        // TravelRequestCreatedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCreatedMail::class, function ($mail) use ($user, $travelRequestData) {
            return $mail->hasTo($user->email) &&
                   $mail->travelRequest->destination === $travelRequestData['destination'] &&
                   $mail->travelRequest->user_id === $user->id;
        });
    }

    public function test_created_email_is_not_sent_when_travel_request_creation_fails(): void
    {
        Mail::fake();
        Event::fake();

        [$user, $token] = $this->authenticatedUser();

        // Try to create with invalid data
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => '',
                'start_date' => now()->subDays(5)->toDateString(), // Past date
                'end_date' => now()->subDays(10)->toDateString(), // Before start date
            ]);

        $response->assertStatus(422);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TravelRequestCreated::class);

        // Assert welcome email was NOT queued
        Mail::assertNothingQueued();
    }

    public function test_created_email_is_not_sent_when_unauthenticated(): void
    {
        Mail::fake();
        Event::fake();

        $response = $this->postJson('/api/travel-requests', [
            'destination' => 'São Paulo, Brasil',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertStatus(401);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TravelRequestCreated::class);

        // Assert email was NOT queued
        Mail::assertNothingQueued();
    }
}

