<?php

namespace App\Mail;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para Email de Cancelamento de Pedido de Viagem
 *
 * Email enviado automaticamente quando um pedido de viagem é cancelado.
 *
 * Características:
 * - Implementa ShouldQueue: é processado assincronamente via RabbitMQ
 * - Template: resources/views/emails/travel-request-cancelled.blade.php
 * - Disparado pelo evento: TravelRequestCancelled
 * - Inclui motivo do cancelamento (se fornecido)
 */
class TravelRequestCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Cria uma nova instância da mensagem.
     *
     * @param TravelRequest $travelRequest Pedido de viagem cancelado
     */
    public function __construct(
        public TravelRequest $travelRequest
    ) {}

    /**
     * Retorna o envelope da mensagem (assunto, remetente, etc.).
     *
     * @return Envelope Configuração do envelope do email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Travel Request Cancelled - ' . $this->travelRequest->destination,
        );
    }

    /**
     * Retorna a definição do conteúdo da mensagem.
     *
     * Define qual template usar e quais variáveis passar para o template.
     * Inclui o motivo do cancelamento se fornecido.
     *
     * @return Content Configuração do conteúdo do email
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.travel-request-cancelled',  // Template Markdown
            with: [
                'travelRequest' => $this->travelRequest,
                'userName' => $this->travelRequest->user->name,
                'destination' => $this->travelRequest->destination,
                'startDate' => $this->travelRequest->start_date,
                'endDate' => $this->travelRequest->end_date,
                'reason' => $this->travelRequest->cancelled_reason,  // Motivo do cancelamento
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
