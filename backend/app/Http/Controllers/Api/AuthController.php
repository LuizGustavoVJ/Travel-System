<?php

namespace App\Http\Controllers\Api;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Controller de Autenticação
 *
 * Gerencia todas as operações relacionadas à autenticação de usuários:
 * - Registro de novos usuários
 * - Login e geração de tokens JWT
 * - Logout e invalidação de tokens
 * - Refresh de tokens
 * - Obtenção de dados do usuário autenticado
 */
class AuthController extends Controller
{
    /**
     * Registra um novo usuário no sistema.
     *
     * Fluxo:
     * 1. Valida dados usando RegisterRequest
     * 2. Cria usuário com role 'user' (não admin)
     * 3. Dispara evento UserRegistered para envio de email de boas-vindas
     * 4. Gera token JWT para o novo usuário
     * 5. Retorna usuário e token
     *
     * @param RegisterRequest $request Dados validados do registro (name, email, password)
     * @return JsonResponse Usuário criado e token JWT
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Cria novo usuário com senha criptografada
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user', // Novos usuários sempre são 'user', não 'admin'
            ]);

            // Dispara evento para envio de email de boas-vindas (processado assincronamente via RabbitMQ)
            event(new UserRegistered($user));

            // Gera token JWT para o novo usuário
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            // Re-lança ValidationException para ser tratada pelo Handler global
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // Trata erro de duplicação de email (código 23000 = violação de constraint única)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Email already registered',
                    'status' => 'error',
                ], 422);
            }

            // Log de outros erros de banco de dados
            Log::error('Error registering user', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while registering the user',
                'status' => 'error',
            ], 500);
        } catch (JWTException $e) {
            // Log de erro na geração do token JWT
            Log::error('JWT error during registration', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'User registered but failed to generate token',
                'status' => 'error',
            ], 500);
        } catch (\Exception $e) {
            // Log de erros genéricos
            Log::error('Error registering user', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while registering the user',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Autentica um usuário e retorna um token JWT.
     *
     * Fluxo:
     * 1. Valida dados usando LoginRequest
     * 2. Tenta autenticar com email e senha
     * 3. Se válido, gera token JWT
     * 4. Retorna usuário e token
     *
     * @param LoginRequest $request Credenciais (email, password)
     * @return JsonResponse Usuário autenticado e token JWT
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Extrai apenas email e senha da requisição
            $credentials = $request->only('email', 'password');

            // Tenta autenticar e gerar token JWT
            if (!$token = JWTAuth::attempt($credentials)) {
                // Credenciais inválidas
                return response()->json([
                    'message' => 'Invalid credentials',
                    'status' => 'error',
                ], 401);
            }

            // Obtém usuário autenticado
            $user = auth()->user();

            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (JWTException $e) {
            // Log de erro na geração do token JWT
            Log::error('JWT error during login', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not create token',
                'status' => 'error',
            ], 500);
        } catch (\Exception $e) {
            // Log de erros genéricos
            Log::error('Error during login', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred during login',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Retorna os dados do usuário autenticado.
     *
     * Requer autenticação via middleware 'auth:api'.
     *
     * @return JsonResponse Dados do usuário autenticado
     */
    public function me(): JsonResponse
    {
        try {
            // Obtém usuário autenticado (injetado pelo middleware JWT)
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                    'status' => 'error',
                ], 401);
            }

            return response()->json([
                'user' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            // Log de erros
            Log::error('Error retrieving user data', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while retrieving user data',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Invalida o token JWT do usuário (logout).
     *
     * Adiciona o token atual à blacklist, impedindo seu uso futuro.
     *
     * @return JsonResponse Mensagem de sucesso
     */
    public function logout(): JsonResponse
    {
        try {
            // Invalida o token atual (adiciona à blacklist)
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Logout successful',
            ]);
        } catch (JWTException $e) {
            // Se o token já foi invalidado ou não existe, considera logout bem-sucedido
            // Isso evita erros se o usuário tentar fazer logout múltiplas vezes
            return response()->json([
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            // Log de erros genéricos
            Log::error('Error during logout', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred during logout',
                'status' => 'error',
            ], 500);
        }
    }

    /**
     * Renova o token JWT do usuário.
     *
     * Gera um novo token JWT com tempo de expiração renovado,
     * mantendo o usuário autenticado sem precisar fazer login novamente.
     *
     * @return JsonResponse Novo token JWT
     */
    public function refresh(): JsonResponse
    {
        try {
            // Gera novo token com expiração renovada
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'token' => $token,
            ]);
        } catch (JWTException $e) {
            // Log de erro na renovação do token
            Log::error('JWT error during token refresh', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not refresh token',
                'status' => 'error',
            ], 401);
        } catch (\Exception $e) {
            // Log de erros genéricos
            Log::error('Error refreshing token', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'An error occurred while refreshing the token',
                'status' => 'error',
            ], 500);
        }
    }
}
