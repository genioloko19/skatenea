<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Core\Router;

final class RouterTest extends TestCase
{
    public function testRouteWithParameterMatches(): void
    {
        $router = new Router();
        $router->get('/api/v1/raffles/{id}', static function (Request $request): Response {
            return Response::data(['id' => $request->param('id')]);
        });

        $response = $router->dispatch(new Request('GET', '/api/v1/raffles/abc-123'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('abc-123', $response->body());
    }

    public function testUnknownRouteThrowsNotFound(): void
    {
        $router = new Router();

        $this->expectException(HttpException::class);
        $router->dispatch(new Request('GET', '/no-existe'));
    }

    public function testMethodMismatchThrowsNotFound(): void
    {
        $router = new Router();
        $router->get('/api/v1/test', static fn (): Response => Response::noContent());

        $this->expectException(HttpException::class);
        $router->dispatch(new Request('POST', '/api/v1/test'));
    }
}
