<?php

declare(strict_types=1);

use Skatenea\Application\Mail\EmailOutboxRepositoryInterface;
use Skatenea\Application\Mail\MailerInterface;
use Skatenea\Core\Config;
use Skatenea\Infrastructure\Mail\MailWorker;

$once = in_array('--once', $argv ?? [], true);

/** @var Skatenea\Core\App $app */
$app = require dirname(__DIR__) . '/config/bootstrap.php';
$c = $app->container();
$worker = new MailWorker(
    $c->get(EmailOutboxRepositoryInterface::class),
    $c->get(MailerInterface::class),
    $c->get(Config::class),
    $c->get(Monolog\Logger::class)
);

do {
    $result = $worker->process(50);
    echo sprintf("[%s] mail worker: enviados=%d fallidos=%d\n", gmdate('c'), $result['sent'], $result['failed']);
    if ($once) {
        break;
    }
    sleep(5);
} while (true);
