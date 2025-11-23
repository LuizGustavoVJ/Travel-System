<?php

namespace App\Listeners;

use App\Events\TravelRequestCreated;
use App\Mail\TravelRequestCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para Envio de Email de Criação de Pedido
 *
 * Escuta o evento TravelRequestCreated e envia um email de confirmação
 * para o usuário que criou o pedido de viagem.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Não bloqueia a resposta HTTP (email é enviado em background)
 * - Disparado automaticamente quando: TravelRequestCreated é disparado
 */
class SendTravelRequestCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Processa o evento TravelRequestCreated.
     *
     * Envia um email de confirmação para o usuário que criou o pedido de viagem.
     * O email é enfileirado no RabbitMQ e processado pelo worker em background.
     *
     * @param TravelRequestCreated $event Evento disparado quando um pedido é criado
     * @return void
     */
    public function handle(TravelRequestCreated $event): void
    {
        $travelRequest = $event->travelRequest;
        $user = $travelRequest->user;

        // Envia email de confirmação (processado assincronamente via RabbitMQ)
        Mail::to($user->email)->send(new TravelRequestCreatedMail($travelRequest));
    }
}

