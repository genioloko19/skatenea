<?php

declare(strict_types=1);

namespace Skatenea\Http\Middleware;

use Skatenea\Auth\AuthService;
use Skatenea\Auth\Rbac;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class AuthMiddleware
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Rbac $rbac
    ) {
    }

    /** @return callable(Request, callable): Response */
    public function require(string $permission = ''): callable
    {
        return function (Request $request, callable $next) use ($permission): Response {
            $token = $request->bearerToken();
            if ($token === null) {
                throw HttpException::unauthorized('Falta el token de acceso.');
            }

            $user = $this->auth->authenticateAccessToken($token, $request);
            $request->setAttribute('user', $user);
            $request->setAttribute('actor_type', 'user');

            if ($permission !== '') {
                $this->rbac->assert($user->role, $permission);
            }

            return $next($request);
        };
    }

    /** @return callable(Request, callable): Response */
    public function optional(): callable
    {
        return function (Request $request, callable $next): Response {
            $token = $request->bearerToken();
            if ($token !== null) {
                try {
                    $user = $this->auth->authenticateAccessToken($token, $request);
                    $request->setAttribute('user', $user);
                    $request->setAttribute('actor_type', 'user');
                } catch (\Throwable) {
                    // Endpoint público: un token inválido no bloquea la consulta.
                }
            }

            return $next($request);
        };
    }
}
