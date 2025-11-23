<?php

namespace Tests\Feature;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserRegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_email_is_sent_when_user_registers(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        // Assert welcome email was queued
        // WelcomeMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(WelcomeMail::class, function ($mail) {
            return $mail->hasTo('john@example.com');
        });
    }

    public function test_user_registered_event_is_dispatched_when_user_registers(): void
    {
        Event::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        // Assert event was dispatched
        Event::assertDispatched(UserRegistered::class, function ($event) {
            return $event->user->email === 'john@example.com';
        });
    }

    public function test_welcome_email_contains_correct_user_information(): void
    {
        Mail::fake();

        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/auth/register', $userData);

        // WelcomeMail implements ShouldQueue, so it's queued even with sync connection
        Mail::assertQueued(WelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']) &&
                   $mail->user->name === $userData['name'] &&
                   $mail->user->email === $userData['email'];
        });
    }

    public function test_welcome_email_is_not_sent_when_registration_fails(): void
    {
        Mail::fake();
        Event::fake();

        // Try to register with invalid data
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertStatus(422);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(UserRegistered::class);

        // Assert welcome email was NOT queued
        Mail::assertNothingQueued();
    }

    public function test_welcome_email_is_not_sent_when_email_already_exists(): void
    {
        Mail::fake();
        Event::fake();

        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Assert event was NOT dispatched
        Event::assertNotDispatched(UserRegistered::class);

        // Assert welcome email was NOT queued
        Mail::assertNothingQueued();
    }
}

