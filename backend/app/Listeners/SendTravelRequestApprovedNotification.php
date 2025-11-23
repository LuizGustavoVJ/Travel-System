<?php

namespace App\Listeners;

use App\Events\TravelRequestApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTravelRequestApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TravelRequestApproved $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Log the notification (in a real app, send email/SMS/push notification)
        Log::info('Travel request approved notification sent', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'travel_request_id' => $travelRequest->id,
            'destination' => $travelRequest->destination,
            'message' => "Your travel request to {$travelRequest->destination} has been approved!",
        ]);

        // TODO: Implement actual notification (email, SMS, push, etc.)
        // Mail::to($user->email)->send(new TravelRequestApprovedMail($travelRequest));
    }
}
