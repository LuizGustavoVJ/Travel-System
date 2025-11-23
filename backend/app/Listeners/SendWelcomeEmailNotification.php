<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para Envio de Email de Boas-Vindas
 *
 * Escuta o evento UserRegistered e envia um email de boas-vindas para o novo usuário.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Não bloqueia a resposta HTTP (email é enviado em background)
 * - Disparado automaticamente quando: UserRegistered é disparado
 */
class SendWelcomeEmailNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Processa o evento UserRegistered.
     *
     * Envia um email de boas-vindas para o usuário que acabou de se registrar.
     * O email é enfileirado no RabbitMQ e processado pelo worker em background.
     *
     * @param UserRegistered $event Evento disparado quando um usuário se registra
     * @return void
     */
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        // Envia email de boas-vindas (processado assincronamente via RabbitMQ)
        Mail::to($user->email)->send(new WelcomeMail($user));
    }
}

