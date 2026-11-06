<?php

declare(strict_types=1);

namespace Skatenea\Http\Middleware;

use Skatenea\Core\Config;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class CorsMiddleware
{
    public function __construct(private readonly Config $config)
    {
    }

    public function __invoke(Request $request, callable $next): Response
    {
        $origin = $request->header('origin');
        $allowed = $this->config->list('CORS_ALLOWED_ORIGINS');
        $headers = ['Vary' => 'Origin'];

        if ($origin !== null && in_array($origin, $allowed, true)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Access-Control-Allow-Methods'] = 'GET, POST, PATCH, DELETE, OPTIONS';
            $headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-CSRF-Token, X-API-Key, X-Request-Id';
            $headers['Access-Control-Max-Age'] = '600';
        }

        if ($request->isMethod('OPTIONS')) {
            return new Response(204, '', $headers);
        }

        $response = $next($request);
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
