<?php

declare(strict_types=1);

use Skatenea\Core\Request;

try {
    /** @var Skatenea\Core\App $app */
    $app = require __DIR__ . '/../config/bootstrap.php';
    $app->run(Request::fromGlobals());
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $debug = getenv('APP_DEBUG') === 'true' || (($_ENV['APP_DEBUG'] ?? '') === 'true');
    echo json_encode([
        'error' => [
            'code' => 'bootstrap_error',
            'message' => 'No se pudo iniciar la API.',
            'details' => $debug ? ['exception' => $e::class, 'message' => $e->getMessage()] : [],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
