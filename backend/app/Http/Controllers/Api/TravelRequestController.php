<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
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
     * Lista todos os pedidos de viagem do usuário autenticado (ou todos se for admin).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Verifica autorização usando Policy
        $this->authorize('viewAny', TravelRequest::class);

        $filters = $request->only(['status', 'destination', 'start_date', 'end_date', 'start_date_from', 'start_date_to', 'created_from', 'created_to']);
        $perPage = $request->input('per_page', 15);

        $travelRequests = $this->service->getAllForUser(auth()->user(), $filters, $perPage);

        return TravelRequestResource::collection($travelRequests);
    }

    /**
     * Cria um novo pedido de viagem.
     */
    public function store(StoreTravelRequestRequest $request): JsonResponse
    {
        // Verifica autorização usando Policy
        $this->authorize('create', TravelRequest::class);

        $travelRequest = $this->service->create(auth()->user(), $request->validated());

        return response()->json([
            'message' => 'Travel request created successfully',
            'data' => new TravelRequestResource($travelRequest),
        ], 201);
    }

    /**
     * Retorna os detalhes de um pedido de viagem específico.
     */
    public function show(string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Verifica autorização usando Policy
        $this->authorize('view', $travelRequest);

        return response()->json([
            'data' => new TravelRequestResource($travelRequest),
        ]);
    }

    /**
     * Atualiza um pedido de viagem existente.
     */
    public function update(UpdateTravelRequestRequest $request, string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Verifica autorização usando Policy (já verifica se é dono e se status permite)
        $this->authorize('update', $travelRequest);

        $updated = $this->service->update($travelRequest, $request->validated());

        return response()->json([
            'message' => 'Travel request updated successfully',
            'data' => new TravelRequestResource($updated),
        ]);
    }

    /**
     * Remove um pedido de viagem (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Verifica autorização usando Policy (já verifica se é dono e se status permite)
        $this->authorize('delete', $travelRequest);

        $this->service->delete($travelRequest);

        return response()->json([
            'message' => 'Travel request deleted successfully',
        ]);
    }

    /**
     * Aprova um pedido de viagem (apenas administradores).
     */
    public function approve(string $id): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Verifica autorização usando Policy
        $this->authorize('approve', $travelRequest);

        $approved = $this->service->approve($travelRequest, auth()->user());

        return response()->json([
            'message' => 'Travel request approved successfully',
            'data' => new TravelRequestResource($approved),
        ]);
    }

    /**
     * Cancela um pedido de viagem (apenas administradores, não pode cancelar se já aprovado).
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        $travelRequest = $this->service->getById($id);

        if (!$travelRequest) {
            return response()->json([
                'message' => 'Travel request not found',
            ], 404);
        }

        // Verifica autorização usando Policy
        $this->authorize('cancel', $travelRequest);

        $reason = $request->input('reason');
        $cancelled = $this->service->cancel($travelRequest, auth()->user(), $reason);

        return response()->json([
            'message' => 'Travel request cancelled successfully',
            'data' => new TravelRequestResource($cancelled),
        ]);
    }
}
