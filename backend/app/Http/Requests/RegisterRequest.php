<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para Registro de Usuário
 *
 * Valida os dados fornecidos no registro de um novo usuário.
 * Usado no endpoint POST /api/auth/register
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     *
     * @return bool Retorna true pois registro é público (não requer autenticação)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras de validação que se aplicam à requisição.
     *
     * Regras:
     * - name: obrigatório, string, máximo 255 caracteres
     * - email: obrigatório, formato email válido, máximo 255 caracteres, único na tabela users
     * - password: obrigatório, string, mínimo 8 caracteres, deve ter confirmação (password_confirmation)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser um texto.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
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
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
        ];
    }
}
