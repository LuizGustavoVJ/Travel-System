<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request para Atualização de Pedido de Viagem
 *
 * Valida os dados fornecidos na atualização de um pedido de viagem existente.
 * Usado no endpoint PUT /api/travel-requests/{id}
 *
 * Características:
 * - Todos os campos são opcionais (usando 'sometimes')
 * - Validação condicional para end_date baseada em start_date
 * - Validação adicional no Service para garantir end_date > start_date do banco
 */
class UpdateTravelRequestRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     *
     * A autorização real é verificada pela Policy no Controller.
     * Este método apenas permite que a validação seja executada.
     *
     * @return bool Retorna true (autorização é feita no Controller via Policy)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras de validação que se aplicam à requisição.
     *
     * Regras base:
     * - destination: opcional, string, máximo 255 caracteres
     * - start_date: opcional, formato data válido, deve ser hoje ou data futura
     * - end_date: opcional, formato data válido (validação condicional abaixo)
     * - notes: opcional, string
     *
     * Validação condicional para end_date:
     * - Se ambos start_date e end_date foram fornecidos: valida end_date > start_date
     * - Se apenas end_date foi fornecido: valida que é >= hoje
     * - Validação completa (end_date > start_date do banco) é feita no Service
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'destination' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'end_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
        ];

        // Validação condicional para end_date
        // Se start_date também foi fornecido, valida end_date > start_date
        // Caso contrário, a validação será feita no Service ou Controller
        if ($this->has('end_date') && $this->has('start_date')) {
            $rules['end_date'][] = 'after:start_date';
        } elseif ($this->has('end_date')) {
            // Se apenas end_date foi fornecido, valida que é uma data futura
            // A validação completa (end_date > start_date do banco) será feita no Service
            $rules['end_date'][] = 'after_or_equal:today';
        }

        return $rules;
    }

    /**
     * Retorna mensagens customizadas para erros de validação.
     *
     * Todas as mensagens estão em português para melhor experiência do usuário.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination.required' => 'O destino é obrigatório.',
            'destination.string' => 'O destino deve ser um texto.',
            'destination.max' => 'O destino não pode ter mais de 255 caracteres.',
            'start_date.required' => 'A data de ida é obrigatória.',
            'start_date.date' => 'A data de ida deve ser uma data válida.',
            'start_date.after_or_equal' => 'A data de ida deve ser hoje ou uma data futura.',
            'end_date.required' => 'A data de volta é obrigatória.',
            'end_date.date' => 'A data de volta deve ser uma data válida.',
            'end_date.after' => 'A data de volta deve ser posterior à data de ida.',
            'notes.string' => 'As observações devem ser um texto.',
        ];
    }

    /**
     * Retorna atributos customizados para erros de validação.
     *
     * Define nomes amigáveis em português para os campos,
     * que aparecerão nas mensagens de erro.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'destination' => 'destino',
            'start_date' => 'data de ida',
            'end_date' => 'data de volta',
            'notes' => 'observações',
        ];
    }
}
