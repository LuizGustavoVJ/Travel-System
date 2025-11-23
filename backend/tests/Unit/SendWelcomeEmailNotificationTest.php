<?php

namespace Tests\Unit;

use App\Events\UserRegistered;
use App\Listeners\SendWelcomeEmailNotification;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendWelcomeEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_sends_welcome_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = new UserRegistered($user);
        $listener = new SendWelcomeEmailNotification();

        $listener->handle($event);

        // WelcomeMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_listener_implements_should_queue(): void
    {
        $listener = new SendWelcomeEmailNotification();

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $listener);
    }

    public function test_listener_uses_correct_mail_class(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $event = new UserRegistered($user);
        $listener = new SendWelcomeEmailNotification();

        $listener->handle($event);

        Mail::assertQueued(WelcomeMail::class);
    }

    public function test_listener_sends_email_to_correct_user(): void
    {
        Mail::fake();

        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $event1 = new UserRegistered($user1);
        $event2 = new UserRegistered($user2);
        $listener = new SendWelcomeEmailNotification();

        $listener->handle($event1);
        $listener->handle($event2);

        // WelcomeMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user1) {
            return $mail->hasTo($user1->email);
        });

        Mail::assertQueued(WelcomeMail::class, function ($mail) use ($user2) {
            return $mail->hasTo($user2->email);
        });
    }
}

