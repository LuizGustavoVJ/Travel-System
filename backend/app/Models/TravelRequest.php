<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model TravelRequest (Pedido de Viagem)
 *
 * Representa um pedido de viagem no sistema.
 *
 * Características:
 * - Usa UUID como chave primária (HasUuids)
 * - Implementa soft deletes (registros não são removidos fisicamente)
 * - Status possíveis: 'requested', 'approved', 'cancelled'
 *
 * Relacionamentos:
 * - belongsTo User (dono do pedido)
 * - belongsTo User via approved_by (quem aprovou)
 * - belongsTo User via cancelled_by (quem cancelou)
 */
class TravelRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa (mass assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',              // ID do usuário que criou o pedido
        'requester_name',      // Nome do solicitante (preenchido automaticamente)
        'destination',         // Destino da viagem
        'start_date',          // Data de ida
        'end_date',            // Data de volta
        'status',              // Status: 'requested', 'approved', 'cancelled'
        'notes',               // Observações adicionais (opcional)
        'approved_by',         // ID do usuário que aprovou (admin)
        'cancelled_by',        // ID do usuário que cancelou
        'cancelled_reason',    // Motivo do cancelamento (opcional)
    ];

    /**
     * Atributos que devem ser convertidos para tipos específicos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',  // Converte para Carbon\Carbon
        'end_date' => 'date',    // Converte para Carbon\Carbon
    ];

    /**
     * Relacionamento: Pedido pertence a um usuário (dono do pedido).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Pedido foi aprovado por um usuário (admin).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relacionamento: Pedido foi cancelado por um usuário.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
