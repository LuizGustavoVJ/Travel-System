<?php

namespace Tests\Unit;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_mail_has_correct_subject(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $mail = new WelcomeMail($user);

        $envelope = $mail->envelope();
        $this->assertStringContainsString('Welcome', $envelope->subject);
    }

    public function test_welcome_mail_has_correct_recipient(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
        ]);

        $mail = new WelcomeMail($user);

        $this->assertEquals($user->email, $mail->user->email);
    }

    public function test_welcome_mail_contains_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user',
        ]);

        $mail = new WelcomeMail($user);

        $content = $mail->content();

        $this->assertEquals('emails.welcome', $content->markdown);
        $this->assertEquals($user->name, $content->with['userName']);
        $this->assertEquals($user->email, $content->with['userEmail']);
        $this->assertEquals($user->id, $content->with['user']->id);
    }

    public function test_welcome_mail_implements_should_queue(): void
    {
        $user = User::factory()->create();
        $mail = new WelcomeMail($user);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    public function test_welcome_mail_has_no_attachments(): void
    {
        $user = User::factory()->create();
        $mail = new WelcomeMail($user);

        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}

