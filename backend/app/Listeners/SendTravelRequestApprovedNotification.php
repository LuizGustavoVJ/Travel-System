<?php

namespace App\Listeners;

use App\Events\TravelRequestApproved;
use App\Mail\TravelRequestApprovedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para Envio de Email de Aprovação de Pedido
 *
 * Escuta o evento TravelRequestApproved e envia um email de notificação
 * para o usuário quando seu pedido de viagem é aprovado por um administrador.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Não bloqueia a resposta HTTP (email é enviado em background)
 * - Disparado automaticamente quando: TravelRequestApproved é disparado
 */
class SendTravelRequestApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Processa o evento TravelRequestApproved.
     *
     * Envia um email de notificação para o usuário quando seu pedido é aprovado.
     * O email é enfileirado no RabbitMQ e processado pelo worker em background.
     *
     * @param TravelRequestApproved $event Evento disparado quando um pedido é aprovado
     * @return void
     */
    public function handle(TravelRequestApproved $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Envia email de notificação de aprovação (processado assincronamente via RabbitMQ)
        Mail::to($user->email)->send(new TravelRequestApprovedMail($travelRequest));
    }
}
