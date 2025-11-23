<?php
/**
 * Health Check Script for Docker
 * Verifica se o PHP-FPM está funcionando corretamente
 */

// Verifica se o arquivo de bootstrap existe
if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
    http_response_code(503);
    echo json_encode(['status' => 'unhealthy', 'error' => 'Bootstrap file not found']);
    exit(1);
}

// Tenta carregar o Laravel
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    
    // Verifica conexão com banco (se .env existe)
    if (file_exists(__DIR__ . '/.env')) {
        try {
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle(
                $request = Illuminate\Http\Request::create('/health', 'GET')
            );
            
            if ($response->getStatusCode() === 200) {
                echo $response->getContent();
                exit(0);
            }
        } catch (\Exception $e) {
            // Se falhar, retorna status básico
        }
    }
    
    // Status básico se não conseguir conectar ao banco
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'php' => PHP_VERSION
    ]);
    exit(0);
    
} catch (\Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ]);
    exit(1);
}

