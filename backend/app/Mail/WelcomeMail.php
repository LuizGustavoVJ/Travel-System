<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para Email de Boas-Vindas
 *
 * Email enviado automaticamente quando um novo usuário se registra no sistema.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Template: resources/views/emails/welcome.blade.php
 * - Disparado pelo evento: UserRegistered
 */
class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Cria uma nova instância da mensagem.
     *
     * @param User $user Usuário que acabou de se registrar
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Retorna o envelope da mensagem (assunto, remetente, etc.).
     *
     * @return Envelope Configuração do envelope do email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . '!',
        );
    }

    /**
     * Retorna a definição do conteúdo da mensagem.
     *
     * Define qual template usar e quais variáveis passar para o template.
     *
     * @return Content Configuração do conteúdo do email
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',  // Template Markdown em resources/views/emails/welcome.blade.php
            with: [
                'user' => $this->user,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
            ],
        );
    }

    /**
     * Retorna os anexos da mensagem.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment> Array de anexos (vazio por padrão)
     */
    public function attachments(): array
    {
        return [];
    }
}

