<?php

namespace App\Listeners;

use App\Events\TravelRequestApproved;
use App\Mail\TravelRequestApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

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

        // Send email notification
        Mail::to($user->email)->send(new TravelRequestApprovedMail($travelRequest));
    }
}
