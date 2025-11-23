<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/**
 * Handler Global de Exceções
 *
 * Centraliza o tratamento de todas as exceções da aplicação.
 * Para requisições de API, retorna respostas JSON padronizadas.
 *
 * Trata os seguintes tipos de exceções:
 * - JWT (Token expirado, inválido, erro)
 * - Autenticação (não autenticado)
 * - Autorização (não autorizado)
 * - Validação (dados inválidos)
 * - Model Not Found (recurso não encontrado)
 * - HTTP (404, 405, etc.)
 * - Banco de dados (QueryException, PDOException)
 * - Genéricas (outras exceções)
 */
class Handler extends ExceptionHandler
{
    /**
     * Lista de campos que nunca devem ser "flashed" para a sessão em exceções de validação.
     *
     * Campos sensíveis como senhas não devem ser armazenados na sessão por segurança.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Registra callbacks de tratamento de exceções para a aplicação.
     *
     * Permite registrar funções que serão executadas quando exceções ocorrerem.
     * Útil para logging customizado ou notificações.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Renderiza uma exceção em uma resposta HTTP.
     *
     * Intercepta todas as exceções e, se for uma requisição de API,
     * retorna resposta JSON padronizada. Caso contrário, usa o comportamento padrão do Laravel.
     *
     * @param  Request  $request Requisição HTTP
     * @param  Throwable  $e Exceção lançada
     * @return Response Resposta HTTP
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response
    {
        // Se for uma requisição de API, retorna JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        // Para requisições web, usa o comportamento padrão do Laravel
        return parent::render($request, $e);
    }

    /**
     * Trata exceções de API e retorna resposta JSON padronizada.
     *
     * Processa diferentes tipos de exceções e retorna respostas JSON consistentes
     * com status HTTP apropriado e mensagens de erro padronizadas.
     *
     * Ordem de verificação:
     * 1. Exceções JWT (401)
     * 2. Exceções de autenticação (401)
     * 3. Exceções de autorização (403)
     * 4. Exceções de validação (422)
     * 5. Exceções de recurso não encontrado (404)
     * 6. Exceções HTTP (404, 405, etc.)
     * 7. Exceções de banco de dados (500)
     * 8. Exceções genéricas (500)
     *
     * @param  Request  $request Requisição HTTP
     * @param  Throwable  $e Exceção lançada
     * @return JsonResponse Resposta JSON com erro padronizado
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // JWT Exceptions
        if ($e instanceof TokenExpiredException) {
            return $this->errorResponse('Token has expired', 401);
        }

        if ($e instanceof TokenInvalidException) {
            return $this->errorResponse('Token is invalid', 401);
        }

        if ($e instanceof JWTException) {
            return $this->errorResponse('Token error', 401);
        }

        // Authentication Exception
        if ($e instanceof AuthenticationException) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Authorization Exception (from authorize() method)
        if ($e instanceof AuthorizationException) {
            return $this->errorResponse('This action is unauthorized.', 403);
        }

        // Validation Exception
        if ($e instanceof ValidationException) {
            return $this->errorResponse(
                'Validation failed',
                422,
                ['errors' => $e->errors()]
            );
        }

        // Model Not Found Exception
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return $this->errorResponse(
                "{$model} not found",
                404
            );
        }

        // Not Found HTTP Exception
        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse('Resource not found', 404);
        }

        // Method Not Allowed Exception
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse(
                'Method not allowed for this route',
                405
            );
        }

        // Access Denied Exception
        if ($e instanceof AccessDeniedHttpException) {
            return $this->errorResponse('This action is unauthorized', 403);
        }

        // Database Exceptions
        if ($e instanceof \Illuminate\Database\QueryException) {
            // Não expor detalhes do banco em produção
            $message = config('app.debug')
                ? $e->getMessage()
                : 'Database error occurred';

            return $this->errorResponse($message, 500);
        }

        // PDO Exceptions
        if ($e instanceof \PDOException) {
            $message = config('app.debug')
                ? $e->getMessage()
                : 'Database connection error';

            return $this->errorResponse($message, 500);
        }

        // Tratamento de exceções genéricas (padrão)
        // Tenta obter o código de status HTTP da exceção, se disponível
        $statusCode = 500; // Código padrão para erros não tratados

        // Verifica se a exceção implementa HttpExceptionInterface (tem getStatusCode)
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
        }

        // Em modo debug, mostra mensagem detalhada; em produção, mensagem genérica
        $message = config('app.debug')
            ? $e->getMessage()
            : 'An error occurred while processing your request';

        return $this->errorResponse(
            $message,
            $statusCode,
            config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ] : null
        );
    }

    /**
     * Retorna uma resposta JSON de erro padronizada.
     *
     * Cria uma resposta JSON consistente com:
     * - message: Mensagem de erro
     * - status: 'error'
     * - data: Dados adicionais (opcional, ex: erros de validação, stack trace em debug)
     *
     * @param  string  $message Mensagem de erro
     * @param  int  $statusCode Código de status HTTP (401, 403, 404, 422, 500, etc.)
     * @param  array|null  $data Dados adicionais (opcional)
     * @return JsonResponse Resposta JSON padronizada
     */
    protected function errorResponse(string $message, int $statusCode, ?array $data = null): JsonResponse
    {
        $response = [
            'message' => $message,
            'status' => 'error',
        ];

        // Adiciona dados extras se fornecidos (ex: erros de validação, stack trace)
        if ($data !== null) {
            $response = array_merge($response, $data);
        }

        return response()->json($response, $statusCode);
    }
}
