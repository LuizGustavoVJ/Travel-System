<?php

namespace App\Listeners;

use App\Events\TravelRequestCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTravelRequestCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TravelRequestCancelled $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Log the notification (in a real app, send email/SMS/push notification)
        Log::info('Travel request cancelled notification sent', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'travel_request_id' => $travelRequest->id,
            'destination' => $travelRequest->destination,
            'reason' => $travelRequest->cancelled_reason,
            'message' => "Your travel request to {$travelRequest->destination} has been cancelled.",
        ]);

        // TODO: Implement actual notification (email, SMS, push, etc.)
        // Mail::to($user->email)->send(new TravelRequestCancelledMail($travelRequest));
    }
}
