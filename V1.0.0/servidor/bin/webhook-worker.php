<?php

declare(strict_types=1);

use Skatenea\Application\Webhook\WebhookRepositoryInterface;
use Skatenea\Core\Config;
use Skatenea\Infrastructure\Webhook\WebhookWorker;

$once = in_array('--once', $argv ?? [], true);

/** @var Skatenea\Core\App $app */
$app = require dirname(__DIR__) . '/config/bootstrap.php';
$c = $app->container();
$worker = new WebhookWorker(
    $c->get(WebhookRepositoryInterface::class),
    $c->get(Config::class),
    $c->get(Monolog\Logger::class)
);

do {
    $result = $worker->process(30);
    echo sprintf("[%s] webhook worker: entregados=%d fallidos=%d\n", gmdate('c'), $result['delivered'], $result['failed']);
    if ($once) {
        break;
    }
    sleep(5);
} while (true);
