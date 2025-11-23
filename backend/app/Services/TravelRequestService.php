<?php

namespace App\Services;

use App\Events\TravelRequestApproved;
use App\Events\TravelRequestCancelled;
use App\Events\TravelRequestCreated;
use App\Models\TravelRequest;
use App\Models\User;
use App\Repositories\TravelRequestRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Service para TravelRequest
 *
 * Contém toda a lógica de negócio relacionada a pedidos de viagem.
 * Esta camada fica entre o Controller e o Repository, implementando regras de negócio
 * e disparando eventos quando necessário.
 */
class TravelRequestService
{
    /**
     * Construtor: injeta o Repository via dependency injection.
     *
     * @param TravelRequestRepository $repository Repository para acesso a dados
     */
    public function __construct(
        private TravelRequestRepository $repository
    ) {}

    /**
     * Busca todos os pedidos de viagem para um usuário.
     *
     * Se o usuário for admin, retorna todos os pedidos.
     * Se for usuário comum, retorna apenas seus próprios pedidos.
     *
     * @param User $user Usuário autenticado
     * @param array $filters Filtros opcionais (status, destination, datas, etc.)
     * @param int $perPage Número de itens por página (padrão: 15)
     * @return LengthAwarePaginator Lista paginada de pedidos de viagem
     */
    public function getAllForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Se for admin, retorna todos os pedidos
        if ($user->isAdmin()) {
            return $this->repository->getAll($filters, $perPage);
        }

        // Se for usuário comum, retorna apenas seus pedidos
        return $this->repository->getAllForUser($user->id, $filters, $perPage);
    }

    /**
     * Busca um pedido de viagem pelo ID (UUID).
     *
     * @param string $id UUID do pedido de viagem
     * @return TravelRequest|null Pedido encontrado ou null se não existir
     */
    public function getById(string $id): ?TravelRequest
    {
        return $this->repository->findById($id);
    }

    /**
     * Cria um novo pedido de viagem.
     *
     * Regras de negócio aplicadas:
     * - user_id é preenchido automaticamente com o ID do usuário autenticado
     * - requester_name é preenchido automaticamente com o nome do usuário
     * - status inicial é sempre 'requested'
     * - Dispara evento TravelRequestCreated para envio de email
     *
     * @param User $user Usuário que está criando o pedido
     * @param array $data Dados do pedido (destination, start_date, end_date, notes)
     * @return TravelRequest Pedido criado
     */
    public function create(User $user, array $data): TravelRequest
    {
        // Preenche campos automaticamente (regra de negócio)
        $data['user_id'] = $user->id;
        $data['requester_name'] = $user->name;
        $data['status'] = 'requested'; // Status inicial sempre é 'requested'

        // Cria o pedido no banco de dados
        $travelRequest = $this->repository->create($data);

        // Dispara evento para notificação por email (processado assincronamente via RabbitMQ)
        event(new TravelRequestCreated($travelRequest));

        return $travelRequest;
    }

    /**
     * Atualiza um pedido de viagem existente.
     *
     * Regras de negócio aplicadas:
     * - Campos protegidos (user_id, status, approved_by, cancelled_by) não podem ser atualizados diretamente
     * - Valida que end_date seja posterior a start_date (se fornecido)
     *
     * @param TravelRequest $travelRequest Pedido a ser atualizado
     * @param array $data Novos dados do pedido
     * @return TravelRequest Pedido atualizado
     * @throws ValidationException Se end_date for anterior ou igual a start_date
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest
    {
        // Remove campos que não devem ser atualizados diretamente (regra de negócio)
        unset($data['user_id'], $data['status'], $data['approved_by'], $data['cancelled_by']);

        // Validação de datas: se end_date foi fornecido, deve ser > start_date
        if (isset($data['end_date'])) {
            // Usa start_date fornecido ou o que está no banco
            $startDate = $data['start_date'] ?? $travelRequest->start_date;

            // Valida que end_date seja posterior a start_date
            if ($startDate && \Carbon\Carbon::parse($data['end_date'])->lte(\Carbon\Carbon::parse($startDate))) {
                $validator = \Illuminate\Support\Facades\Validator::make([], []);
                $validator->errors()->add('end_date', 'A data de volta deve ser posterior à data de ida.');
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }

        // Atualiza o pedido no banco de dados
        return $this->repository->update($travelRequest, $data);
    }

    /**
     * Deleta um pedido de viagem (soft delete).
     *
     * @param TravelRequest $travelRequest Pedido a ser deletado
     * @return bool True se deletado com sucesso
     */
    public function delete(TravelRequest $travelRequest): bool
    {
        return $this->repository->delete($travelRequest);
    }

    /**
     * Aprova um pedido de viagem.
     *
     * Regras de negócio aplicadas:
     * - Apenas administradores podem aprovar (verificado pela Policy)
     * - Status deve ser 'requested' (verificado pela Policy)
     * - Dispara evento TravelRequestApproved para envio de email
     *
     * @param TravelRequest $travelRequest Pedido a ser aprovado
     * @param User $approver Usuário que está aprovando (deve ser admin)
     * @return TravelRequest Pedido aprovado
     */
    public function approve(TravelRequest $travelRequest, User $approver): TravelRequest
    {
        // Aprova o pedido no banco de dados
        $approved = $this->repository->approve($travelRequest, $approver->id);

        // Dispara evento para notificação por email (processado assincronamente via RabbitMQ)
        event(new TravelRequestApproved($approved));

        return $approved;
    }

    /**
     * Cancela um pedido de viagem.
     *
     * Regras de negócio aplicadas:
     * - Admin pode cancelar qualquer pedido não aprovado (verificado pela Policy)
     * - Dono pode cancelar seu próprio pedido não aprovado (verificado pela Policy)
     * - Pedidos aprovados não podem ser cancelados (verificado pela Policy)
     * - Dispara evento TravelRequestCancelled para envio de email
     *
     * @param TravelRequest $travelRequest Pedido a ser cancelado
     * @param User $canceller Usuário que está cancelando
     * @param string|null $reason Motivo do cancelamento (opcional)
     * @return TravelRequest Pedido cancelado
     */
    public function cancel(TravelRequest $travelRequest, User $canceller, ?string $reason = null): TravelRequest
    {
        // Cancela o pedido no banco de dados
        $cancelled = $this->repository->cancel($travelRequest, $canceller->id, $reason);

        // Dispara evento para notificação por email (processado assincronamente via RabbitMQ)
        event(new TravelRequestCancelled($cancelled));

        return $cancelled;
    }
}
