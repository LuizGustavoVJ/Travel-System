<?php

namespace Tests\Unit;

use App\Events\TravelRequestCreated;
use App\Listeners\SendTravelRequestCreatedNotification;
use App\Mail\TravelRequestCreatedMail;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendTravelRequestCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_sends_travel_request_created_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $event = new TravelRequestCreated($travelRequest);
        $listener = new SendTravelRequestCreatedNotification();

        $listener->handle($event);

        // TravelRequestCreatedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCreatedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = new SendTravelRequestCreatedNotification();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }

    public function test_listener_uses_correct_mail_class(): void
    {
        Mail::fake();

        $travelRequest = TravelRequest::factory()->create();
        $event = new TravelRequestCreated($travelRequest);
        $listener = new SendTravelRequestCreatedNotification();

        $listener->handle($event);

        Mail::assertQueued(TravelRequestCreatedMail::class);
    }

    public function test_listener_sends_email_to_correct_user(): void
    {
        Mail::fake();

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $travelRequest1 = TravelRequest::factory()->create(['user_id' => $user1->id]);
        $travelRequest2 = TravelRequest::factory()->create(['user_id' => $user2->id]);

        $event1 = new TravelRequestCreated($travelRequest1);
        $event2 = new TravelRequestCreated($travelRequest2);
        $listener = new SendTravelRequestCreatedNotification();

        $listener->handle($event1);
        $listener->handle($event2);

        // TravelRequestCreatedMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(TravelRequestCreatedMail::class, function ($mail) use ($user1) {
            return $mail->hasTo($user1->email);
        });

        Mail::assertQueued(TravelRequestCreatedMail::class, function ($mail) use ($user2) {
            return $mail->hasTo($user2->email);
        });
    }
}

