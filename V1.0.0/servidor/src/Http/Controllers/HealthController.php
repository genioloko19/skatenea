<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Core\Config;
use Skatenea\Core\Database;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class HealthController extends BaseController
{
    public function __construct(
        private readonly Database $database,
        private readonly Config $config
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $dbOk = true;
        try {
            $this->database->pdo()->query('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        return Response::data([
            'status' => $dbOk ? 'ok' : 'degraded',
            'version' => '1.0.0',
            'environment' => $this->config->string('APP_ENV', 'production'),
            'database' => $dbOk ? 'ok' : 'error',
            'timestamp' => gmdate('c'),
        ], $dbOk ? 200 : 503);
    }
}
