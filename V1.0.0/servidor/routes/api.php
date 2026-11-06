<?php

declare(strict_types=1);

use Skatenea\Core\App;
use Skatenea\Core\Config;
use Skatenea\Core\Container;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Http\Controllers\ApiKeyController;
use Skatenea\Http\Controllers\AuditController;
use Skatenea\Http\Controllers\AuthController;
use Skatenea\Http\Controllers\BuyerController;
use Skatenea\Http\Controllers\DashboardController;
use Skatenea\Http\Controllers\HealthController;
use Skatenea\Http\Controllers\PreferencesController;
use Skatenea\Http\Controllers\RaffleController;
use Skatenea\Http\Controllers\SettingsController;
use Skatenea\Http\Controllers\TicketController;
use Skatenea\Http\Controllers\UserController;
use Skatenea\Http\Middleware\ApiKeyMiddleware;
use Skatenea\Http\Middleware\AuthMiddleware;
use Skatenea\Http\Middleware\RateLimitMiddleware;

return static function (App $app, Container $container): void {
    $router = $app->router();
    $config = $container->get(Config::class);
    $auth = $container->get(AuthMiddleware::class);
    $apiKey = $container->get(ApiKeyMiddleware::class);
    $rate = $container->get(RateLimitMiddleware::class);

    $authController = $container->get(AuthController::class);
    $health = $container->get(HealthController::class);
    $dashboard = $container->get(DashboardController::class);
    $raffles = $container->get(RaffleController::class);
    $tickets = $container->get(TicketController::class);
    $buyers = $container->get(BuyerController::class);
    $users = $container->get(UserController::class);
    $apiKeys = $container->get(ApiKeyController::class);
    $audit = $container->get(AuditController::class);
    $settings = $container->get(SettingsController::class);
    $preferences = $container->get(PreferencesController::class);

    $loginLimit = $rate->limit(
        'login',
        $config->int('LOGIN_MAX_ATTEMPTS', 8),
        $config->int('LOGIN_WINDOW_SECONDS', 900)
    );
    $registerLimit = $rate->limit('register', 12, 3600);
    $refreshLimit = $rate->limit('refresh', 60, 60);

    $router->get('/', static fn (Request $request): Response => Response::data([
        'name' => 'SKATENEA API',
        'version' => '1.0.0',
        'documentation' => '/docs/api/openapi.yaml',
    ]));

    $router->get('/api/v1/health', $health);

    $router->post('/api/v1/auth/login', [$authController, 'login'], [$loginLimit]);
    $router->post('/api/v1/auth/refresh', [$authController, 'refresh'], [$refreshLimit]);
    $router->post('/api/v1/auth/logout', [$authController, 'logout']);
    $router->post('/api/v1/auth/register', [$authController, 'register'], [$registerLimit]);
    $router->get('/api/v1/auth/me', [$authController, 'me'], [$auth->require()]);
    $router->patch('/api/v1/auth/password', [$authController, 'changePassword'], [$auth->require('profile.update')]);

    $router->get('/api/v1/public/raffles', [$raffles, 'index'], [$auth->optional()]);
    $router->get('/api/v1/public/raffles/{id}', [$raffles, 'show'], [$auth->optional()]);
    $router->get('/api/v1/public/tickets/verify/{token}', [$raffles, 'verify']);

    // Integración máquina-a-máquina (API key con scope raffles.read).
    $router->get('/api/v1/integrations/raffles', [$raffles, 'index'], [
        $apiKey->require('raffles.read'),
    ]);

    $router->get('/api/v1/dashboard', [$dashboard, 'summary'], [$auth->require('dashboard.read')]);

    $router->get('/api/v1/raffles', [$raffles, 'index'], [$auth->optional()]);
    $router->get('/api/v1/raffles/{id}', [$raffles, 'show'], [$auth->optional()]);
    $router->post('/api/v1/raffles', [$raffles, 'store'], [$auth->require('raffles.create')]);
    $router->patch('/api/v1/raffles/{id}', [$raffles, 'update'], [$auth->require('raffles.update')]);
    $router->post('/api/v1/raffles/{id}/submit', [$raffles, 'submit'], [$auth->require('raffles.submit')]);
    $router->post('/api/v1/raffles/{id}/approve', [$raffles, 'approve'], [$auth->require('raffles.approve')]);
    $router->post('/api/v1/raffles/{id}/reject', [$raffles, 'reject'], [$auth->require('raffles.approve')]);
    $router->post('/api/v1/raffles/{id}/activate', [$raffles, 'activate'], [$auth->require('raffles.activate')]);
    $router->post('/api/v1/raffles/{id}/close', [$raffles, 'close'], [$auth->require('raffles.close')]);
    $router->post('/api/v1/raffles/{id}/cancel', [$raffles, 'cancel'], [$auth->require('raffles.delete')]);
    $router->post('/api/v1/raffles/{id}/sellers', [$raffles, 'assignSellers'], [$auth->require('tickets.assign')]);
    $router->post('/api/v1/raffles/{id}/tickets/generate', [$raffles, 'generateTickets'], [$auth->require('tickets.assign')]);
    $router->post('/api/v1/raffles/{id}/tickets/assign', [$raffles, 'assignTickets'], [$auth->require('tickets.assign')]);
    $router->post('/api/v1/raffles/{id}/draw', [$raffles, 'draw'], [$auth->require('raffles.approve')]);
    $router->get('/api/v1/raffles/{id}/approvals', [$raffles, 'approvals'], [$auth->require('raffles.read')]);
    $router->get('/api/v1/raffles/{id}/tickets', [$tickets, 'byRaffle'], [$auth->require('tickets.read')]);

    $router->get('/api/v1/tickets/mine', [$tickets, 'mine'], [$auth->require('tickets.read')]);
    $router->get('/api/v1/tickets/{id}', [$tickets, 'show'], [$auth->require('tickets.read')]);
    $router->post('/api/v1/tickets/{id}/reserve', [$tickets, 'reserve'], [$auth->require('tickets.reserve')]);
    $router->post('/api/v1/tickets/{id}/pay', [$tickets, 'pay'], [$auth->require('tickets.pay')]);
    $router->post('/api/v1/tickets/{id}/cancel', [$tickets, 'cancel'], [$auth->require('tickets.cancel')]);

    $router->get('/api/v1/buyers', [$buyers, 'search'], [$auth->require('buyers.read')]);
    $router->get('/api/v1/buyers/{id}', [$buyers, 'show'], [$auth->require('buyers.read')]);
    $router->post('/api/v1/buyers', [$buyers, 'store'], [$auth->require('buyers.create')]);
    $router->patch('/api/v1/buyers/{id}', [$buyers, 'update'], [$auth->require('buyers.update')]);

    $router->get('/api/v1/sellers', [$users, 'sellers'], [$auth->require('tickets.assign')]);
    $router->get('/api/v1/users', [$users, 'index'], [$auth->require('users.read')]);
    $router->get('/api/v1/users/{id}', [$users, 'show'], [$auth->require('users.read')]);
    $router->post('/api/v1/users', [$users, 'store'], [$auth->require('users.create')]);
    $router->patch('/api/v1/users/{id}', [$users, 'update'], [$auth->require('users.update')]);
    $router->delete('/api/v1/users/{id}', [$users, 'deactivate'], [$auth->require('users.deactivate')]);
    $router->patch('/api/v1/profile', [$users, 'updateProfile'], [$auth->require('profile.update')]);
    $router->get('/api/v1/preferences', [$preferences, 'show'], [$auth->require('profile.update')]);
    $router->patch('/api/v1/preferences', [$preferences, 'update'], [$auth->require('profile.update')]);

    $router->get('/api/v1/api-keys', [$apiKeys, 'index'], [$auth->require('api_keys.read')]);
    $router->post('/api/v1/api-keys', [$apiKeys, 'store'], [$auth->require('api_keys.create')]);
    $router->delete('/api/v1/api-keys/{id}', [$apiKeys, 'revoke'], [$auth->require('api_keys.revoke')]);

    $router->get('/api/v1/audit', [$audit, 'index'], [$auth->require('audit.read')]);

    $router->get('/api/v1/settings', [$settings, 'index'], [$auth->require('settings.read')]);
    $router->patch('/api/v1/settings', [$settings, 'update'], [$auth->require('settings.update')]);
};
