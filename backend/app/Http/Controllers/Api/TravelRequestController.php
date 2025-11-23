<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
use App\Services\TravelRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller de Pedidos de Viagem
 *
 * Gerencia todas as operações CRUD e ações especiais relacionadas a pedidos de viagem:
 * - Listar pedidos (com filtros e paginação)
 * - Criar novo pedido
 * - Visualizar pedido específico
 * - Atualizar pedido
 * - Deletar pedido (soft delete)
 * - Aprovar pedido (apenas admin)
 * - Cancelar pedido
 *
 * Todas as rotas requerem autenticação via middleware 'auth:api'.
 * Autorização é verificada usando TravelRequestPolicy via $this->authorize().
 */
class TravelRequestController extends Controller
{
    /**
     * Construtor: injeta o Service via dependency injection.
     *
     * @param TravelRequestService $service Service com lógica de negócio
     */
    public function __construct(
        private TravelRequestService $service
    ) {}

    /**
     * Lista todos os pedidos de viagem do usuário autenticado (ou todos se for admin).
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            // Verifica autorização usando Policy
            $this->authorize('viewAny', TravelRequest::class);

            $filters = $request->only(['status', 'destination', 'start_date', 'end_date', 'start_date_from', 'start_date_to', 'created_from', 'created_to']);
            $perPage = $request->input('per_page', 15);

            $travelRequests = $this->service->getAllForUser(auth()->user(), $filters, $perPage);

            return TravelRequestResource::collection($travelRequests);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (\Exception $e) {
            Log::error('Error listing travel requests', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while listing travel requests',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Cria um novo pedido de viagem.
     */
    public function store(StoreTravelRequestRequest $request): JsonResponse
    {
        try {
            // Verifica autorização usando Policy
            $this->authorize('create', TravelRequest::class);

            $travelRequest = $this->service->create(auth()->user(), $request->validated());

            return response()->json([
                'message' => 'Travel request created successfully',
                'data' => new TravelRequestResource($travelRequest),
            ], 201);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ValidationException $e) {
            throw $e; // ValidationException já é tratada pelo Handler
        } catch (\Exception $e) {
            Log::error('Error creating travel request', [
                'user_id' => auth()->id(),
                'data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the travel request',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Retorna os detalhes de um pedido de viagem específico.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $travelRequest = $this->service->getById($id);

            if (!$travelRequest) {
                return response()->json([
                    'message' => 'Travel request not found',
                    'status' => 'error',
                ], 404);
            }

            // Verifica autorização usando Policy
            $this->authorize('view', $travelRequest);

            return response()->json([
                'data' => new TravelRequestResource($travelRequest),
            ]);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error showing travel request', [
                'travel_request_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while retrieving the travel request',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Atualiza um pedido de viagem existente.
     */
    public function update(UpdateTravelRequestRequest $request, string $id): JsonResponse
    {
        try {
            $travelRequest = $this->service->getById($id);

            if (!$travelRequest) {
                return response()->json([
                    'message' => 'Travel request not found',
                    'status' => 'error',
                ], 404);
            }

            // Verifica autorização usando Policy (já verifica se é dono e se status permite)
            $this->authorize('update', $travelRequest);

            $updated = $this->service->update($travelRequest, $request->validated());

            return response()->json([
                'message' => 'Travel request updated successfully',
                'data' => new TravelRequestResource($updated),
            ]);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ValidationException $e) {
            throw $e; // ValidationException já é tratada pelo Handler
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating travel request', [
                'travel_request_id' => $id,
                'user_id' => auth()->id(),
                'data' => $request->validated(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the travel request',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Remove um pedido de viagem (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $travelRequest = $this->service->getById($id);

            if (!$travelRequest) {
                return response()->json([
                    'message' => 'Travel request not found',
                    'status' => 'error',
                ], 404);
            }

            // Verifica autorização usando Policy (já verifica se é dono e se status permite)
            $this->authorize('delete', $travelRequest);

            $this->service->delete($travelRequest);

            return response()->json([
                'message' => 'Travel request deleted successfully',
            ]);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting travel request', [
                'travel_request_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting the travel request',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Aprova um pedido de viagem (apenas administradores).
     */
    public function approve(string $id): JsonResponse
    {
        try {
            $travelRequest = $this->service->getById($id);

            if (!$travelRequest) {
                return response()->json([
                    'message' => 'Travel request not found',
                    'status' => 'error',
                ], 404);
            }

            // Verifica autorização usando Policy
            $this->authorize('approve', $travelRequest);

            $approved = $this->service->approve($travelRequest, auth()->user());

            return response()->json([
                'message' => 'Travel request approved successfully',
                'data' => new TravelRequestResource($approved),
            ]);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error approving travel request', [
                'travel_request_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while approving the travel request',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Cancela um pedido de viagem (apenas administradores, não pode cancelar se já aprovado).
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        try {
            $travelRequest = $this->service->getById($id);

            if (!$travelRequest) {
                return response()->json([
                    'message' => 'Travel request not found',
                    'status' => 'error',
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
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            throw $e; // Deixa o Handler tratar exceções de autorização
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Travel request not found',
                'status' => 'error',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error cancelling travel request', [
                'travel_request_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while cancelling the travel request',
                'status' => 'error',
            ], 500);
        }
    }
}
