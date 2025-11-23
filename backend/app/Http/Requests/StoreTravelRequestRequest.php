<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para Criação de Pedido de Viagem
 *
 * Valida os dados fornecidos na criação de um novo pedido de viagem.
 * Usado no endpoint POST /api/travel-requests
 *
 * Nota: user_id e requester_name são preenchidos automaticamente pelo Service.
 */
class StoreTravelRequestRequest extends FormRequest
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
     * Regras:
     * - destination: obrigatório, string, máximo 255 caracteres
     * - start_date: obrigatório, formato data válido, deve ser hoje ou data futura
     * - end_date: obrigatório, formato data válido, deve ser posterior a start_date
     * - notes: opcional, string (observações adicionais)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string'],
        ];
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
