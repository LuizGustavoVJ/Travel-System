<?php

namespace App\Listeners;

use App\Events\TravelRequestCreated;
use App\Mail\TravelRequestCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendTravelRequestCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TravelRequestCreated $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Send email notification
        Mail::to($user->email)->send(new TravelRequestCreatedMail($travelRequest));
    }
}

