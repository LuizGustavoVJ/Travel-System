<?php

namespace Tests\Feature;

use App\Events\TravelRequestApproved;
use App\Mail\TravelRequestApprovedMail;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TravelRequestApprovedEmailTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedAdmin(): array
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);
        return [$admin, $token];
    }

    public function test_approved_email_is_sent_when_travel_request_is_approved(): void
    {
        Mail::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(200);

        // TravelRequestApprovedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestApprovedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_travel_request_approved_event_is_dispatched_when_approved(): void
    {
        Event::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(200);

        // Assert event was dispatched
        Event::assertDispatched(TravelRequestApproved::class, function ($event) use ($travelRequest) {
            return $event->travelRequest->id === $travelRequest->id;
        });
    }

    public function test_approved_email_contains_correct_travel_request_information(): void
    {
        Mail::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'destination' => 'São Paulo, Brasil',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        // TravelRequestApprovedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestApprovedMail::class, function ($mail) use ($user, $travelRequest) {
            return $mail->hasTo($user->email) &&
                   $mail->travelRequest->id === $travelRequest->id &&
                   $mail->travelRequest->destination === 'São Paulo, Brasil';
        });
    }

    public function test_approved_email_is_not_sent_when_approval_fails(): void
    {
        Mail::fake();
        Event::fake();

        $user = User::factory()->create();
        $token = auth()->login($user);
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        // Regular user cannot approve
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/approve");

        $response->assertStatus(403);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TravelRequestApproved::class);

        // Assert email was NOT queued
        Mail::assertNothingQueued();
    }
}

