<?php

namespace App\Listeners;

use App\Events\TravelRequestCancelled;
use App\Mail\TravelRequestCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

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

        // Send email notification
        Mail::to($user->email)->send(new TravelRequestCancelledMail($travelRequest));
    }
}
