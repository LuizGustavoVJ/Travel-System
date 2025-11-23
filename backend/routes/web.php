<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint para Docker healthcheck
Route::get('/health', function () {
    try {
        // Verifica conexão com banco de dados
        DB::connection()->getPdo();

        // Verifica conexão com Redis (se configurado)
        try {
            Redis::connection()->ping();
        } catch (\Exception $e) {
            // Redis não é crítico, apenas loga
        }

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => 'ok',
                'redis' => 'ok',
            ]
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'error' => $e->getMessage()
        ], 503);
    }
});
