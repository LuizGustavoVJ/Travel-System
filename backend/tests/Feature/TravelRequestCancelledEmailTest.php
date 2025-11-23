<?php

namespace Tests\Feature;

use App\Events\TravelRequestCancelled;
use App\Mail\TravelRequestCancelledMail;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TravelRequestCancelledEmailTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedAdmin(): array
    {
        $admin = User::factory()->admin()->create();
        $token = auth()->login($admin);
        return [$admin, $token];
    }

    public function test_cancelled_email_is_sent_when_travel_request_is_cancelled_by_admin(): void
    {
        Mail::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Budget constraints',
            ]);

        $response->assertStatus(200);

        // TravelRequestCancelledMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCancelledMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_cancelled_email_is_sent_when_user_cancels_their_own_request(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $token = auth()->login($user);
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Personal reasons',
            ]);

        $response->assertStatus(200);

        // TravelRequestCancelledMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCancelledMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_travel_request_cancelled_event_is_dispatched_when_cancelled(): void
    {
        Event::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'requested']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Budget constraints',
            ]);

        $response->assertStatus(200);

        // Assert event was dispatched
        Event::assertDispatched(TravelRequestCancelled::class, function ($event) use ($travelRequest) {
            return $event->travelRequest->id === $travelRequest->id;
        });
    }

    public function test_cancelled_email_contains_correct_travel_request_information(): void
    {
        Mail::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $user = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'destination' => 'Rio de Janeiro, Brasil',
        ]);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel", [
                'reason' => 'Budget constraints',
            ]);

        // TravelRequestCancelledMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCancelledMail::class, function ($mail) use ($user, $travelRequest) {
            return $mail->hasTo($user->email) &&
                   $mail->travelRequest->id === $travelRequest->id &&
                   $mail->travelRequest->destination === 'Rio de Janeiro, Brasil' &&
                   $mail->travelRequest->cancelled_reason === 'Budget constraints';
        });
    }

    public function test_cancelled_email_is_not_sent_when_cancellation_fails(): void
    {
        Mail::fake();
        Event::fake();

        [$admin, $token] = $this->authenticatedAdmin();
        $travelRequest = TravelRequest::factory()->create(['status' => 'approved']);

        // Cannot cancel approved request
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson("/api/travel-requests/{$travelRequest->id}/cancel");

        $response->assertStatus(403);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(TravelRequestCancelled::class);

        // Assert email was NOT queued
        Mail::assertNothingQueued();
    }
}

