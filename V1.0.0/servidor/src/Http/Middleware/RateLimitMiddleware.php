<?php

declare(strict_types=1);

namespace Skatenea\Http\Middleware;

use Skatenea\Core\RateLimiter;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class RateLimitMiddleware
{
    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    /** @return callable(Request, callable): Response */
    public function limit(string $name, int $max, int $windowSeconds): callable
    {
        return function (Request $request, callable $next) use ($name, $max, $windowSeconds): Response {
            $this->limiter->hit($name . ':' . $request->ip(), $max, $windowSeconds);

            return $next($request);
        };
    }
}
