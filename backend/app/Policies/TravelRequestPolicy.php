<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy de Autorização para TravelRequest
 *
 * Define as regras de autorização para todas as operações relacionadas a pedidos de viagem.
 * Esta classe é usada automaticamente pelo Laravel quando $this->authorize() é chamado no Controller.
 */
class TravelRequestPolicy
{
    /**
     * Verifica se o usuário pode listar todos os pedidos de viagem.
     *
     * @param User $user Usuário autenticado
     * @return bool Retorna true se o usuário pode listar pedidos
     */
    public function viewAny(User $user): bool
    {
        // Todos os usuários autenticados podem listar pedidos de viagem
        return true;
    }

    /**
     * Verifica se o usuário pode visualizar um pedido específico.
     *
     * @param User $user Usuário autenticado
     * @param TravelRequest $travelRequest Pedido de viagem a ser visualizado
     * @return bool Retorna true se o usuário pode visualizar o pedido
     */
    public function view(User $user, TravelRequest $travelRequest): bool
    {
        // Admin pode ver qualquer pedido OU usuário pode ver apenas seus próprios pedidos
        return $user->isAdmin() || $travelRequest->user_id === $user->id;
    }

    /**
     * Verifica se o usuário pode criar um novo pedido de viagem.
     *
     * @param User $user Usuário autenticado
     * @return bool Retorna true se o usuário pode criar pedidos
     */
    public function create(User $user): bool
    {
        // Todos os usuários autenticados podem criar pedidos de viagem
        return true;
    }

    /**
     * Verifica se o usuário pode atualizar um pedido de viagem.
     *
     * @param User $user Usuário autenticado
     * @param TravelRequest $travelRequest Pedido de viagem a ser atualizado
     * @return bool Retorna true se o usuário pode atualizar o pedido
     */
    public function update(User $user, TravelRequest $travelRequest): bool
    {
        // Apenas o dono pode atualizar seu próprio pedido
        // E o pedido não pode estar aprovado ou cancelado
        return $travelRequest->user_id === $user->id
            && !in_array($travelRequest->status, ['approved', 'cancelled']);
    }

    /**
     * Verifica se o usuário pode deletar um pedido de viagem.
     *
     * @param User $user Usuário autenticado
     * @param TravelRequest $travelRequest Pedido de viagem a ser deletado
     * @return bool Retorna true se o usuário pode deletar o pedido
     */
    public function delete(User $user, TravelRequest $travelRequest): bool
    {
        // Apenas o dono pode deletar seu próprio pedido
        // E o pedido não pode estar aprovado ou cancelado
        return $travelRequest->user_id === $user->id
            && !in_array($travelRequest->status, ['approved', 'cancelled']);
    }

    /**
     * Verifica se o usuário pode aprovar um pedido de viagem.
     *
     * @param User $user Usuário autenticado
     * @param TravelRequest $travelRequest Pedido de viagem a ser aprovado
     * @return bool Retorna true se o usuário pode aprovar o pedido
     */
    public function approve(User $user, TravelRequest $travelRequest): bool
    {
        // Apenas administradores podem aprovar pedidos
        // E o pedido deve estar com status 'requested'
        return $user->isAdmin() && $travelRequest->status === 'requested';
    }

    /**
     * Verifica se o usuário pode cancelar um pedido de viagem.
     *
     * @param User $user Usuário autenticado
     * @param TravelRequest $travelRequest Pedido de viagem a ser cancelado
     * @return bool Retorna true se o usuário pode cancelar o pedido
     */
    public function cancel(User $user, TravelRequest $travelRequest): bool
    {
        // Administradores podem cancelar qualquer pedido que não esteja aprovado
        if ($user->isAdmin() && $travelRequest->status !== 'approved') {
            return true;
        }

        // Donos podem cancelar seu próprio pedido se não estiver aprovado
        return $travelRequest->user_id === $user->id && $travelRequest->status !== 'approved';
    }
}
