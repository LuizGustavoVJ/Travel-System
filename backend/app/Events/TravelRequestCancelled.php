<?php

namespace App\Events;

use App\Models\TravelRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelRequestCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TravelRequest $travelRequest
    ) {}
}
