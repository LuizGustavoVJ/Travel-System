<?php

namespace App\Listeners;

use App\Events\TravelRequestCancelled;
use App\Mail\TravelRequestCancelledMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para Envio de Email de Cancelamento de Pedido
 *
 * Escuta o evento TravelRequestCancelled e envia um email de notificação
 * para o usuário quando seu pedido de viagem é cancelado.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Não bloqueia a resposta HTTP (email é enviado em background)
 * - Disparado automaticamente quando: TravelRequestCancelled é disparado
 * - Inclui motivo do cancelamento no email (se fornecido)
 */
class SendTravelRequestCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Processa o evento TravelRequestCancelled.
     *
     * Envia um email de notificação para o usuário quando seu pedido é cancelado.
     * O email é enfileirado no RabbitMQ e processado pelo worker em background.
     *
     * @param TravelRequestCancelled $event Evento disparado quando um pedido é cancelado
     * @return void
     */
    public function handle(TravelRequestCancelled $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Envia email de notificação de cancelamento (processado assincronamente via RabbitMQ)
        Mail::to($user->email)->send(new TravelRequestCancelledMail($travelRequest));
    }
}
