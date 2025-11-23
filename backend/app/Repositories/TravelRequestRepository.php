<?php

namespace App\Repositories;

use App\Models\TravelRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TravelRequestRepository
{
    /**
     * Get all travel requests for a user with optional filters.
     */
    public function getAllForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = TravelRequest::where('user_id', $userId);

        $query = $this->applyFilters($query, $filters);

        return $query->with(['user', 'approver', 'canceller'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Get all travel requests (admin) with optional filters.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = TravelRequest::query();

        $query = $this->applyFilters($query, $filters);

        return $query->with(['user', 'approver', 'canceller'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Find a travel request by ID.
     */
    public function findById(string $id): ?TravelRequest
    {
        return TravelRequest::with(['user', 'approver', 'canceller'])->find($id);
    }

    /**
     * Create a new travel request.
     */
    public function create(array $data): TravelRequest
    {
        return TravelRequest::create($data);
    }

    /**
     * Update a travel request.
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest
    {
        $travelRequest->update($data);
        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }

    /**
     * Delete a travel request.
     */
    public function delete(TravelRequest $travelRequest): bool
    {
        return $travelRequest->delete();
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    /**
     * Approve a travel request.
     */
    public function approve(TravelRequest $travelRequest, int $approvedBy): TravelRequest
    {
        $travelRequest->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);

        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }

    /**
     * Cancel a travel request.
     */
    public function cancel(TravelRequest $travelRequest, int $cancelledBy, ?string $reason = null): TravelRequest
    {
        $travelRequest->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy,
            'cancelled_reason' => $reason,
        ]);

        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }
}
