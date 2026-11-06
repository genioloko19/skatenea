<?php

declare(strict_types=1);

namespace Skatenea\Http\Middleware;

use Skatenea\Auth\ApiKeyService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class ApiKeyMiddleware
{
    public function __construct(private readonly ApiKeyService $apiKeys)
    {
    }

    /** @return callable(Request, callable): Response */
    public function require(string $permission = ''): callable
    {
        return function (Request $request, callable $next) use ($permission): Response {
            $rawKey = $request->header('x-api-key');
            if ($rawKey === null || $rawKey === '') {
                throw HttpException::unauthorized('Falta la API key.');
            }

            $record = $this->apiKeys->verify($rawKey, $request);
            if ($record === null) {
                throw HttpException::unauthorized('API key inválida o revocada.');
            }

            $scopes = is_array($record['scopes'] ?? null) ? $record['scopes'] : [];
            if ($permission !== '' && !in_array('*', $scopes, true) && !in_array($permission, $scopes, true)) {
                throw HttpException::forbidden('La API key no tiene el scope requerido: ' . $permission);
            }

            $request->setAttribute('api_key', $record);
            $request->setAttribute('actor_type', 'api_key');

            return $next($request);
        };
    }
}
