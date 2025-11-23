<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Resources\TravelRequestResource;
use App\Services\TravelRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TravelRequestController extends Controller
{
    public function __construct(
        private TravelRequestService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'destination', 'start_date', 'end_date', 'created_from', 'created_to']);
        
        $travelRequests = $this->service->getAllForUser(auth()->user(), $filters);

        return TravelRequestResource::collection($travelRequests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTravelRequestRequest $request): JsonResponse
    {
        $travelRequest = $this->service->create(auth()->user(), $request->validated());

        return response()->json([
            'message' => 'Travel request created successfully',
            'data' => new TravelRequestResource($travelRequest),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Check authorization
        if (!auth()->user()->isAdmin() && $travelRequest->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'data' => new TravelRequestResource($travelRequest),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTravelRequestRequest $request, string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Check authorization
        if ($travelRequest->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $updated = $this->service->update($travelRequest, $request->validated());

        return response()->json([
            'message' => 'Travel request updated successfully',
            'data' => new TravelRequestResource($updated),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Check authorization
        if ($travelRequest->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->service->delete($travelRequest);

        return response()->json([
            'message' => 'Travel request deleted successfully',
        ]);
    }
}
