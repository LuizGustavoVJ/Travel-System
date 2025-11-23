<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TravelRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can list travel requests
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TravelRequest $travelRequest): bool
    {
        return $user->isAdmin() || $travelRequest->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create travel requests
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TravelRequest $travelRequest): bool
    {
        // Only the owner can update their own travel request
        // And the request must not be approved or cancelled
        return $travelRequest->user_id === $user->id
            && !in_array($travelRequest->status, ['approved', 'cancelled']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TravelRequest $travelRequest): bool
    {
        // Only the owner can delete their own travel request
        // And the request must not be approved or cancelled
        return $travelRequest->user_id === $user->id
            && !in_array($travelRequest->status, ['approved', 'cancelled']);
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, TravelRequest $travelRequest): bool
    {
        // Only admins can approve travel requests
        // And the request must be in 'requested' status
        return $user->isAdmin() && $travelRequest->status === 'requested';
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, TravelRequest $travelRequest): bool
    {
        // Admins can cancel any request that is not approved
        if ($user->isAdmin() && $travelRequest->status !== 'approved') {
            return true;
        }

        // Owners can cancel their own request if it's not approved
        return $travelRequest->user_id === $user->id && $travelRequest->status !== 'approved';
    }
}
