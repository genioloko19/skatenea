<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class Router
{
    /** @var list<array{method: string, pattern: string, regex: string, handler: callable, middleware: list<callable>}> */
    private array $routes = [];

    /**
     * @param callable(Request): Response $handler
     * @param list<callable> $middleware
     */
    public function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $normalized = '/' . trim($pattern, '/');
        $parts = preg_split(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            $normalized,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        ) ?: [$normalized];

        $regex = '';
        foreach ($parts as $index => $part) {
            $regex .= $index % 2 === 0
                ? preg_quote($part, '#')
                : '(?P<' . $part . '>[^/]+)';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $normalized,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /** @param list<callable> $middleware */
    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function patch(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('PATCH', $pattern, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function delete(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->isMethod('HEAD') ? 'GET' : $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            $request->setParams($params);

            $pipeline = array_reverse($route['middleware']);
            $handler = $route['handler'];
            $next = static function (Request $req) use ($handler): Response {
                return $handler($req);
            };

            foreach ($pipeline as $middleware) {
                $next = static fn (Request $req): Response => $middleware($req, $next);
            }

            return $next($request);
        }

        throw HttpException::notFound('Ruta no encontrada: ' . $request->method() . ' ' . $request->path());
    }

    /** @return list<array{method: string, pattern: string}> */
    public function routes(): array
    {
        return array_map(
            static fn (array $route): array => ['method' => $route['method'], 'pattern' => $route['pattern']],
            $this->routes
        );
    }
}
