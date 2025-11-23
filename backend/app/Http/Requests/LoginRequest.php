<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para Login de Usuário
 *
 * Valida os dados fornecidos no login de um usuário.
 * Usado no endpoint POST /api/auth/login
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     *
     * @return bool Retorna true pois login é público (não requer autenticação)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retorna as regras de validação que se aplicam à requisição.
     *
     * Regras:
     * - email: obrigatório, formato email válido, máximo 255 caracteres
     * - password: obrigatório, string
     *
     * Nota: A validação de credenciais (email/senha corretos) é feita no AuthController.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
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
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser um texto.',
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
            'email' => 'e-mail',
            'password' => 'senha',
        ];
    }
}
