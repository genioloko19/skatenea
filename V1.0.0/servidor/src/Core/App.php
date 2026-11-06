<?php

declare(strict_types=1);

namespace Skatenea\Core;

use Monolog\Logger as Monolog;

final class App
{
    /** @var list<callable> */
    private array $middleware = [];

    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly Config $config,
        private readonly Monolog $logger
    ) {
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function run(Request $request): void
    {
        $requestId = $request->header('x-request-id') ?: Uuid::v4();
        $request->setAttribute('request_id', $requestId);

        $next = function (Request $req): Response {
            return $this->router->dispatch($req);
        };

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn (Request $req): Response => $middleware($req, $next);
        }

        try {
            $response = $next($request);
        } catch (HttpException $e) {
            $response = Response::error($e->errorCode, $e->getMessage(), $e->status, $e->details);
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'exception' => $e,
            ]);

            $details = $this->config->isDebug()
                ? ['exception' => $e::class, 'message' => $e->getMessage()]
                : [];

            $response = Response::error(
                'internal_error',
                'Ocurrió un error interno. Intenta nuevamente o contacta al soporte.',
                500,
                $details
            );
        }

        $response = $response
            ->withHeader('X-Request-Id', $requestId)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        if ($this->config->isProduction()) {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->send();
    }
}
