<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Model User (Usuário)
 *
 * Representa um usuário do sistema.
 *
 * Características:
 * - Implementa JWTSubject para autenticação JWT
 * - Roles possíveis: 'admin', 'user'
 * - Senha é automaticamente criptografada (cast 'hashed')
 *
 * Relacionamentos:
 * - hasMany TravelRequest (pedidos de viagem do usuário)
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Campos que podem ser preenchidos em massa (mass assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',         // Nome do usuário
        'email',       // Email (único)
        'password',    // Senha (criptografada automaticamente)
        'role',        // Role: 'admin' ou 'user'
    ];

    /**
     * Atributos que devem ser ocultados na serialização (não aparecem em JSON).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',        // Senha nunca deve ser exposta
        'remember_token',  // Token de "lembrar-me"
    ];

    /**
     * Atributos que devem ser convertidos para tipos específicos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',  // Converte para Carbon\Carbon
            'password' => 'hashed',             // Criptografa automaticamente ao salvar
        ];
    }

    /**
     * Retorna o identificador que será armazenado no claim 'sub' do JWT.
     *
     * @return mixed ID do usuário
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Retorna claims customizados a serem adicionados ao JWT.
     *
     * @return array Claims customizados (role)
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,  // Adiciona role ao token JWT
        ];
    }

    /**
     * Verifica se o usuário é administrador.
     *
     * @return bool True se role for 'admin'
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Relacionamento: Usuário tem muitos pedidos de viagem.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function travelRequests()
    {
        return $this->hasMany(TravelRequest::class);
    }
}
