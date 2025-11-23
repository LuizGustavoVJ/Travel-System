<?php

namespace Tests\Unit;

use App\Mail\TravelRequestCreatedMail;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestCreatedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_travel_request_created_mail_has_correct_subject(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'destination' => 'São Paulo, Brasil',
        ]);

        $mail = new TravelRequestCreatedMail($travelRequest);

        $envelope = $mail->envelope();
        $this->assertStringContainsString('Travel Request Created', $envelope->subject);
        $this->assertStringContainsString('São Paulo, Brasil', $envelope->subject);
    }

    public function test_travel_request_created_mail_has_correct_recipient(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $mail = new TravelRequestCreatedMail($travelRequest);

        $this->assertEquals($user->id, $mail->travelRequest->user_id);
    }

    public function test_travel_request_created_mail_contains_travel_request_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
        ]);
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'destination' => 'Rio de Janeiro, Brasil',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $mail = new TravelRequestCreatedMail($travelRequest);

        $content = $mail->content();

        $this->assertEquals('emails.travel-request-created', $content->markdown);
        $this->assertEquals($user->name, $content->with['userName']);
        $this->assertEquals($travelRequest->destination, $content->with['destination']);
        $this->assertEquals($travelRequest->id, $content->with['travelRequest']->id);
    }

    public function test_travel_request_created_mail_implements_should_queue(): void
    {
        $travelRequest = TravelRequest::factory()->create();
        $mail = new TravelRequestCreatedMail($travelRequest);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    public function test_travel_request_created_mail_has_no_attachments(): void
    {
        $travelRequest = TravelRequest::factory()->create();
        $mail = new TravelRequestCreatedMail($travelRequest);

        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }
}

