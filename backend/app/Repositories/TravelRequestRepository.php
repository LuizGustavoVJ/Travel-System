<?php

namespace App\Repositories;

use App\Models\TravelRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository para TravelRequest
 *
 * Responsável por todas as operações de acesso a dados relacionadas a pedidos de viagem.
 * Esta camada abstrai a lógica de queries do banco de dados, facilitando testes e manutenção.
 */
class TravelRequestRepository
{
    /**
     * Busca todos os pedidos de viagem de um usuário específico com filtros opcionais.
     *
     * @param int $userId ID do usuário
     * @param array $filters Filtros opcionais (status, destination, datas, etc.)
     * @param int $perPage Número de itens por página (padrão: 15)
     * @return LengthAwarePaginator Lista paginada de pedidos de viagem
     */
    public function getAllForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Busca apenas pedidos do usuário especificado
        $query = TravelRequest::where('user_id', $userId);

        // Aplica filtros se fornecidos
        $query = $this->applyFilters($query, $filters);

        // Carrega relacionamentos e ordena por data de criação (mais recentes primeiro)
        return $query->with(['user', 'approver', 'canceller'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Busca todos os pedidos de viagem (para administradores) com filtros opcionais.
     *
     * @param array $filters Filtros opcionais (status, destination, datas, etc.)
     * @param int $perPage Número de itens por página (padrão: 15)
     * @return LengthAwarePaginator Lista paginada de todos os pedidos de viagem
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Busca todos os pedidos (sem filtro de usuário)
        $query = TravelRequest::query();

        // Aplica filtros se fornecidos
        $query = $this->applyFilters($query, $filters);

        // Carrega relacionamentos e ordena por data de criação (mais recentes primeiro)
        return $query->with(['user', 'approver', 'canceller'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Busca um pedido de viagem pelo ID (UUID).
     *
     * @param string $id UUID do pedido de viagem
     * @return TravelRequest|null Pedido encontrado ou null se não existir
     */
    public function findById(string $id): ?TravelRequest
    {
        // Busca pedido com todos os relacionamentos carregados
        return TravelRequest::with(['user', 'approver', 'canceller'])->find($id);
    }

    /**
     * Cria um novo pedido de viagem no banco de dados.
     *
     * @param array $data Dados do pedido (destination, start_date, end_date, etc.)
     * @return TravelRequest Pedido criado
     */
    public function create(array $data): TravelRequest
    {
        return TravelRequest::create($data);
    }

    /**
     * Atualiza um pedido de viagem existente.
     *
     * @param TravelRequest $travelRequest Pedido a ser atualizado
     * @param array $data Novos dados do pedido
     * @return TravelRequest Pedido atualizado com relacionamentos recarregados
     */
    public function update(TravelRequest $travelRequest, array $data): TravelRequest
    {
        // Atualiza o pedido
        $travelRequest->update($data);

        // Retorna o pedido atualizado com relacionamentos recarregados do banco
        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }

    /**
     * Deleta um pedido de viagem (soft delete).
     *
     * @param TravelRequest $travelRequest Pedido a ser deletado
     * @return bool True se deletado com sucesso
     */
    public function delete(TravelRequest $travelRequest): bool
    {
        // Soft delete: marca deleted_at, mas não remove do banco
        return $travelRequest->delete();
    }

    /**
     * Aplica filtros à query de busca.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Query do Eloquent
     * @param array $filters Array de filtros a serem aplicados
     * @return \Illuminate\Database\Eloquent\Builder Query com filtros aplicados
     */
    private function applyFilters($query, array $filters)
    {
        // Filtro por status (requested, approved, cancelled)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por destino (busca parcial, case-insensitive)
        if (!empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }

        // Filtro por data de ida (a partir de)
        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        // Filtro por data de volta (até)
        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', $filters['end_date']);
        }

        // Filtro por data de ida (período: de)
        if (!empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }

        // Filtro por data de ida (período: até)
        if (!empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        // Filtro por data de criação (período: de)
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        // Filtro por data de criação (período: até)
        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        return $query;
    }

    /**
     * Aprova um pedido de viagem.
     *
     * @param TravelRequest $travelRequest Pedido a ser aprovado
     * @param int $approvedBy ID do usuário que está aprovando (admin)
     * @return TravelRequest Pedido aprovado com relacionamentos recarregados
     */
    public function approve(TravelRequest $travelRequest, int $approvedBy): TravelRequest
    {
        // Atualiza status para 'approved' e registra quem aprovou
        $travelRequest->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);

        // Retorna pedido atualizado com relacionamentos recarregados
        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }

    /**
     * Cancela um pedido de viagem.
     *
     * @param TravelRequest $travelRequest Pedido a ser cancelado
     * @param int $cancelledBy ID do usuário que está cancelando
     * @param string|null $reason Motivo do cancelamento (opcional)
     * @return TravelRequest Pedido cancelado com relacionamentos recarregados
     */
    public function cancel(TravelRequest $travelRequest, int $cancelledBy, ?string $reason = null): TravelRequest
    {
        // Atualiza status para 'cancelled', registra quem cancelou e o motivo
        $travelRequest->update([
            'status' => 'cancelled',
            'cancelled_by' => $cancelledBy,
            'cancelled_reason' => $reason,
        ]);

        // Retorna pedido atualizado com relacionamentos recarregados
        return $travelRequest->fresh(['user', 'approver', 'canceller']);
    }
}
