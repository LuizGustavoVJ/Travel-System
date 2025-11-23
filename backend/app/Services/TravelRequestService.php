<?php

namespace App\Services;

use App\Events\TravelRequestApproved;
use App\Events\TravelRequestCancelled;
use App\Models\TravelRequest;
use App\Models\User;
use App\Repositories\TravelRequestRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class TravelRequestService
{
    public function __construct(
        private TravelRequestRepository $repository
    ) {}

    /**
     * Get all travel requests for a user.
     */
    public function getAllForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        if ($user->isAdmin()) {
            return $this->repository->getAll($filters);
        }

        return $this->repository->getAllForUser($user->id, $filters);
    }

    /**
     * Get a travel request by ID.
     */
    public function getById(string $id): ?TravelRequest
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a new travel request.
     */
    public function create(User $user, array $data): TravelRequest
    {
        $data['user_id'] = $user->id;
        $data['requester_name'] = $user->name;
        $data['status'] = 'requested';

        return $this->repository->create($data);
    }

    /**
     * Update a travel request.
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest
    {
        // Remove fields that shouldn't be updated directly
        unset($data['user_id'], $data['status'], $data['approved_by'], $data['cancelled_by']);

        return $this->repository->update($travelRequest, $data);
    }

    /**
     * Delete a travel request.
     */
    public function delete(TravelRequest $travelRequest): bool
    {
        return $this->repository->delete($travelRequest);
    }

    /**
     * Approve a travel request.
     */
    public function approve(TravelRequest $travelRequest, User $approver): TravelRequest
    {
        $approved = $this->repository->approve($travelRequest, $approver->id);
        
        // Dispatch event for notification
        event(new TravelRequestApproved($approved));
        
        return $approved;
    }

    /**
     * Cancel a travel request.
     */
    public function cancel(TravelRequest $travelRequest, User $canceller, ?string $reason = null): TravelRequest
    {
        $cancelled = $this->repository->cancel($travelRequest, $canceller->id, $reason);
        
        // Dispatch event for notification
        event(new TravelRequestCancelled($cancelled));
        
        return $cancelled;
    }
}
