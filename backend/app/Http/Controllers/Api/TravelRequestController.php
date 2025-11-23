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
     * Lista todos os pedidos de viagem do usuário autenticado (ou todos se for admin).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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

        // Verifica autorização
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

        // Verifica autorização
        if ($travelRequest->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Não permite atualizar pedidos aprovados ou cancelados
        if (in_array($travelRequest->status, ['approved', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot update a travel request that is already ' . $travelRequest->status,
            ], 403);
        }

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

        // Verifica autorização
        if ($travelRequest->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Não permite deletar pedidos aprovados ou cancelados
        if (in_array($travelRequest->status, ['approved', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot delete a travel request that is already ' . $travelRequest->status,
            ], 403);
        }

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

        // Verifica autorização using Policy
        if (!auth()->user()->can('approve', $travelRequest)) {
            return response()->json([
                'message' => 'Unauthorized. Only admins can approve travel requests.',
            ], 403);
        }

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
        if (!auth()->user()->can('cancel', $travelRequest)) {
            return response()->json([
                'message' => 'Unauthorized. Cannot cancel an approved travel request.',
            ], 403);
        }

        $reason = $request->input('reason');
        $cancelled = $this->service->cancel($travelRequest, auth()->user(), $reason);

        return response()->json([
            'message' => 'Travel request cancelled successfully',
            'data' => new TravelRequestResource($cancelled),
        ]);
    }
}
