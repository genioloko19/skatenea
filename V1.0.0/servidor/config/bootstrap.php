<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Skatenea\Application\AuditLogger;
use Skatenea\Application\DashboardService;
use Skatenea\Application\EventService;
use Skatenea\Application\Export\ExportService;
use Skatenea\Application\Mail\MailerInterface;
use Skatenea\Application\Mail\MailService;
use Skatenea\Application\Notifications\NotificationRepositoryInterface;
use Skatenea\Application\Notifications\NotificationService;
use Skatenea\Application\PasswordReset\PasswordResetRepositoryInterface;
use Skatenea\Application\PasswordReset\PasswordResetService;
use Skatenea\Application\PreferencesService;
use Skatenea\Application\Print\PrintJobRepositoryInterface;
use Skatenea\Application\Print\PrintService;
use Skatenea\Application\Raffle\RaffleService;
use Skatenea\Application\Receipt\ReceiptService;
use Skatenea\Application\SettingsService;
use Skatenea\Application\Ticket\BuyerService;
use Skatenea\Application\Ticket\TicketService;
use Skatenea\Application\User\UserService;
use Skatenea\Application\Webhook\WebhookRepositoryInterface;
use Skatenea\Application\Webhook\WebhookService;
use Skatenea\Auth\ApiKeyService;
use Skatenea\Auth\AuthService;
use Skatenea\Auth\CookieService;
use Skatenea\Auth\JwtService;
use Skatenea\Auth\PasswordPolicy;
use Skatenea\Auth\PasswordService;
use Skatenea\Auth\Rbac;
use Skatenea\Core\App;
use Skatenea\Core\Config;
use Skatenea\Core\Container;
use Skatenea\Core\Database;
use Skatenea\Core\Logger;
use Skatenea\Core\RateLimiter;
use Skatenea\Core\Router;
use Skatenea\Domain\Audit\AuditRepositoryInterface;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Payment\PaymentRepositoryInterface;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\UserRepositoryInterface;
use Skatenea\Http\Controllers\ApiKeyController;
use Skatenea\Http\Controllers\AuditController;
use Skatenea\Http\Controllers\AuthController;
use Skatenea\Http\Controllers\BuyerController;
use Skatenea\Http\Controllers\DashboardController;
use Skatenea\Http\Controllers\HealthController;
use Skatenea\Http\Controllers\NotificationController;
use Skatenea\Http\Controllers\PreferencesController;
use Skatenea\Http\Controllers\PrintController;
use Skatenea\Http\Controllers\RaffleController;
use Skatenea\Http\Controllers\SettingsController;
use Skatenea\Http\Controllers\TicketController;
use Skatenea\Http\Controllers\UserController;
use Skatenea\Http\Controllers\WebhookController;
use Skatenea\Http\Middleware\ApiKeyMiddleware;
use Skatenea\Http\Middleware\AuthMiddleware;
use Skatenea\Http\Middleware\CorsMiddleware;
use Skatenea\Http\Middleware\RateLimitMiddleware;
use Skatenea\Infrastructure\Mail\LogMailer;
use Skatenea\Infrastructure\Mail\NullMailer;
use Skatenea\Infrastructure\Mail\SmtpMailer;
use Skatenea\Infrastructure\Persistence\PdoApiKeyRepository;
use Skatenea\Infrastructure\Persistence\PdoAuditRepository;
use Skatenea\Infrastructure\Persistence\PdoBuyerRepository;
use Skatenea\Infrastructure\Persistence\PdoEmailOutboxRepository;
use Skatenea\Infrastructure\Persistence\PdoNotificationRepository;
use Skatenea\Infrastructure\Persistence\PdoPasswordResetRepository;
use Skatenea\Infrastructure\Persistence\PdoPaymentRepository;
use Skatenea\Infrastructure\Persistence\PdoPreferencesRepository;
use Skatenea\Infrastructure\Persistence\PdoPrintJobRepository;
use Skatenea\Infrastructure\Persistence\PdoRaffleRepository;
use Skatenea\Infrastructure\Persistence\PdoRefreshTokenRepository;
use Skatenea\Infrastructure\Persistence\PdoSettingsRepository;
use Skatenea\Infrastructure\Persistence\PdoTicketRepository;
use Skatenea\Infrastructure\Persistence\PdoUserRepository;
use Skatenea\Infrastructure\Persistence\PdoWebhookRepository;
use Skatenea\Infrastructure\Persistence\PreferencesRepositoryInterface;
use Skatenea\Infrastructure\Persistence\SettingsRepositoryInterface;

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

$env = Dotenv::createArrayBacked($root)->safeLoad();
$values = array_merge(
    [
        'APP_NAME' => 'SKATENEA',
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'http://localhost:8000',
        'APP_TIMEZONE' => 'UTC',
        'DB_DRIVER' => 'sqlite',
        'DB_SQLITE_PATH' => '../bd/skatenea.sqlite',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'skatenea',
        'DB_USER' => 'root',
        'DB_PASS' => '',
        'DB_CHARSET' => 'utf8mb4',
        'JWT_ISSUER' => 'skatenea-api',
        'JWT_AUDIENCE' => 'skatenea-client',
        'JWT_ACCESS_TTL' => '900',
        'JWT_LEEWAY' => '30',
        'COOKIE_DOMAIN' => '',
        'COOKIE_SECURE' => 'false',
        'COOKIE_SAMESITE' => 'Lax',
        'REFRESH_TTL' => '2592000',
        'CORS_ALLOWED_ORIGINS' => 'http://localhost:5173,http://127.0.0.1:5173',
        'LOGIN_MAX_ATTEMPTS' => '8',
        'LOGIN_WINDOW_SECONDS' => '900',
        'RATE_LIMIT_MAX' => '120',
        'RATE_LIMIT_WINDOW' => '60',
        'PASSWORD_MIN_LENGTH' => '12',
        'PASSWORD_RESET_TTL_MINUTES' => '30',
        'PUBLIC_WEB_URL' => 'http://localhost:5173',
        'MAIL_DRIVER' => 'log',
        'MAIL_ENABLED' => 'true',
        'MAIL_HOST' => '',
        'MAIL_PORT' => '587',
        'MAIL_USER' => '',
        'MAIL_PASS' => '',
        'MAIL_FROM' => 'no-reply@skatenea.local',
        'MAIL_FROM_NAME' => 'SKATENEA',
        'MAIL_MAX_ATTEMPTS' => '5',
        'WEBHOOK_MAX_ATTEMPTS' => '5',
        'WEBHOOK_ALLOW_PRIVATE' => 'false',
        'PRINT_DRIVER' => 'file',
        'PRINT_HOST' => '127.0.0.1',
        'PRINT_PORT' => '9100',
        'PRINT_WINDOWS_SHARE' => 'POS-80',
    ],
    $env,
    $_ENV,
    $_SERVER
);

$config = new Config($values);
date_default_timezone_set($config->string('APP_TIMEZONE', 'UTC'));

if ($config->isDebug()) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

$logger = Logger::create($config);
$database = new Database($config);
$pdo = $database->pdo();
$container = new Container();

$container->set(Config::class, $config);
$container->set(Database::class, $database);
$container->set(PDO::class, $pdo);
$container->set(Container::class, $container);
$container->set(Monolog\Logger::class, $logger);

$container->set(UserRepositoryInterface::class, new PdoUserRepository($pdo));
$container->set(BuyerRepositoryInterface::class, new PdoBuyerRepository($pdo));
$container->set(RaffleRepositoryInterface::class, new PdoRaffleRepository($pdo));
$container->set(TicketRepositoryInterface::class, new PdoTicketRepository($pdo));
$container->set(PaymentRepositoryInterface::class, new PdoPaymentRepository($pdo));
$container->set(AuditRepositoryInterface::class, new PdoAuditRepository($pdo));
$container->set(Skatenea\Auth\RefreshTokenRepositoryInterface::class, new PdoRefreshTokenRepository($pdo));
$container->set(Skatenea\Auth\ApiKeyRepositoryInterface::class, new PdoApiKeyRepository($pdo));
$container->set(SettingsRepositoryInterface::class, new PdoSettingsRepository($pdo));
$container->set(PreferencesRepositoryInterface::class, new PdoPreferencesRepository($pdo));
$container->set(Skatenea\Application\Mail\EmailOutboxRepositoryInterface::class, new PdoEmailOutboxRepository($pdo));
$container->set(NotificationRepositoryInterface::class, new PdoNotificationRepository($pdo));
$container->set(PasswordResetRepositoryInterface::class, new PdoPasswordResetRepository($pdo));
$container->set(WebhookRepositoryInterface::class, new PdoWebhookRepository($pdo));
$container->set(PrintJobRepositoryInterface::class, new PdoPrintJobRepository($pdo));

$mailer = match ($config->string('MAIL_DRIVER', 'log')) {
    'smtp' => new SmtpMailer($config),
    'null' => new NullMailer(),
    default => new LogMailer($root . '/storage/logs/mail.log'),
};
$container->set(MailerInterface::class, $mailer);

$container->set(PasswordService::class, new PasswordService());
$container->set(PasswordPolicy::class, new PasswordPolicy($config));
$container->set(JwtService::class, new JwtService($config));
$container->set(Rbac::class, new Rbac());
$container->set(CookieService::class, new CookieService($config));
$container->set(AuditLogger::class, new AuditLogger($container->get(AuditRepositoryInterface::class)));
$container->set(NotificationService::class, new NotificationService(
    $container->get(NotificationRepositoryInterface::class)
));
$container->set(WebhookService::class, new WebhookService(
    $container->get(WebhookRepositoryInterface::class),
    $container->get(AuditLogger::class),
    $config
));
$container->set(MailService::class, new MailService(
    $container->get(Skatenea\Application\Mail\EmailOutboxRepositoryInterface::class),
    $config
));
$container->set(EventService::class, new EventService(
    $container->get(NotificationService::class),
    $container->get(WebhookService::class),
    $container->get(MailService::class),
    $container->get(UserRepositoryInterface::class),
    $logger
));

$container->set(AuthService::class, new AuthService(
    $container->get(UserRepositoryInterface::class),
    $container->get(BuyerRepositoryInterface::class),
    $container->get(Skatenea\Auth\RefreshTokenRepositoryInterface::class),
    $container->get(JwtService::class),
    $container->get(PasswordService::class),
    $container->get(AuditLogger::class),
    $container->get(MailService::class),
    $config,
    $database
));

$container->set(ApiKeyService::class, new ApiKeyService(
    $container->get(Skatenea\Auth\ApiKeyRepositoryInterface::class),
    $container->get(AuditLogger::class)
));

$container->set(BuyerService::class, new BuyerService(
    $container->get(BuyerRepositoryInterface::class),
    $container->get(AuditLogger::class)
));

$container->set(TicketService::class, new TicketService(
    $container->get(TicketRepositoryInterface::class),
    $container->get(RaffleRepositoryInterface::class),
    $container->get(BuyerRepositoryInterface::class),
    $container->get(BuyerService::class),
    $container->get(PaymentRepositoryInterface::class),
    $container->get(AuditLogger::class),
    $container->get(EventService::class),
    $database
));

$container->set(RaffleService::class, new RaffleService(
    $container->get(RaffleRepositoryInterface::class),
    $container->get(TicketRepositoryInterface::class),
    $container->get(UserRepositoryInterface::class),
    $container->get(AuditLogger::class),
    $container->get(EventService::class),
    $database
));

$container->set(UserService::class, new UserService(
    $container->get(UserRepositoryInterface::class),
    $container->get(Skatenea\Auth\RefreshTokenRepositoryInterface::class),
    $container->get(PasswordService::class),
    $container->get(PasswordPolicy::class),
    $container->get(AuditLogger::class)
));

$container->set(DashboardService::class, new DashboardService(
    $container->get(RaffleRepositoryInterface::class),
    $container->get(TicketRepositoryInterface::class),
    $container->get(UserRepositoryInterface::class),
    $container->get(BuyerRepositoryInterface::class)
));

$container->set(SettingsService::class, new SettingsService(
    $container->get(SettingsRepositoryInterface::class),
    $container->get(AuditLogger::class)
));

$container->set(PreferencesService::class, new PreferencesService(
    $container->get(PreferencesRepositoryInterface::class)
));
$container->set(ExportService::class, new ExportService());
$container->set(ReceiptService::class, new ReceiptService(
    $container->get(RaffleRepositoryInterface::class),
    $container->get(BuyerRepositoryInterface::class),
    $config
));
$container->set(PrintService::class, new PrintService(
    $container->get(PrintJobRepositoryInterface::class),
    $container->get(RaffleRepositoryInterface::class),
    $container->get(BuyerRepositoryInterface::class),
    $container->get(AuditLogger::class),
    $config
));
$container->set(PasswordResetService::class, new PasswordResetService(
    $container->get(UserRepositoryInterface::class),
    $container->get(PasswordResetRepositoryInterface::class),
    $container->get(PasswordService::class),
    $container->get(PasswordPolicy::class),
    $container->get(Skatenea\Auth\RefreshTokenRepositoryInterface::class),
    $container->get(MailService::class),
    $container->get(AuditLogger::class),
    $database,
    $config
));

$container->set(AuthController::class, new AuthController(
    $container->get(AuthService::class),
    $container->get(CookieService::class),
    $container->get(Rbac::class),
    $container->get(PreferencesService::class),
    $container->get(PasswordResetService::class)
));
$container->set(HealthController::class, new HealthController($database, $config));
$container->set(DashboardController::class, new DashboardController($container->get(DashboardService::class)));
$container->set(RaffleController::class, new RaffleController(
    $container->get(RaffleService::class),
    $container->get(RaffleRepositoryInterface::class),
    $container->get(TicketRepositoryInterface::class),
    $container->get(ExportService::class)
));
$container->set(TicketController::class, new TicketController(
    $container->get(TicketService::class),
    $container->get(TicketRepositoryInterface::class),
    $container->get(RaffleRepositoryInterface::class),
    $container->get(RaffleService::class),
    $container->get(BuyerRepositoryInterface::class),
    $container->get(ReceiptService::class)
));
$container->set(BuyerController::class, new BuyerController($container->get(BuyerService::class)));
$container->set(UserController::class, new UserController($container->get(UserService::class)));
$container->set(ApiKeyController::class, new ApiKeyController($container->get(ApiKeyService::class)));
$container->set(AuditController::class, new AuditController(
    $container->get(AuditRepositoryInterface::class),
    $container->get(ExportService::class)
));
$container->set(SettingsController::class, new SettingsController($container->get(SettingsService::class)));
$container->set(PreferencesController::class, new PreferencesController($container->get(PreferencesService::class)));
$container->set(NotificationController::class, new NotificationController($container->get(NotificationService::class)));
$container->set(WebhookController::class, new WebhookController($container->get(WebhookService::class)));
$container->set(PrintController::class, new PrintController(
    $container->get(PrintService::class),
    $container->get(TicketService::class)
));

$container->set(CorsMiddleware::class, new CorsMiddleware($config));
$container->set(AuthMiddleware::class, new AuthMiddleware(
    $container->get(AuthService::class),
    $container->get(Rbac::class)
));
$container->set(ApiKeyMiddleware::class, new ApiKeyMiddleware($container->get(ApiKeyService::class)));
$container->set(RateLimitMiddleware::class, new RateLimitMiddleware(
    new RateLimiter($root . '/storage/rate')
));

$router = new Router();
$app = new App($container, $router, $config, $logger);
$app->addMiddleware($container->get(CorsMiddleware::class));
$app->addMiddleware($container->get(RateLimitMiddleware::class)->limit(
    'global',
    $config->int('RATE_LIMIT_MAX', 120),
    $config->int('RATE_LIMIT_WINDOW', 60)
));

(require $root . '/routes/api.php')($app, $container);

return $app;
