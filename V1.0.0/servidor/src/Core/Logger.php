<?php

declare(strict_types=1);

namespace Skatenea\Core;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;

final class Logger
{
    public static function create(Config $config): Monolog
    {
        $logger = new Monolog('skatenea');
        $path = dirname(__DIR__, 2) . '/storage/logs/app.log';
        $level = $config->isDebug() ? Level::Debug : Level::Info;

        if (is_dir(dirname($path)) && is_writable(dirname($path))) {
            $handler = new RotatingFileHandler($path, 14, $level);
        } else {
            $handler = new StreamHandler('php://stderr', $level);
        }

        $handler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        ));
        $logger->pushHandler($handler);

        return $logger;
    }
}
