<?php

declare(strict_types=1);

use Skatenea\Application\Print\PrintJobRepositoryInterface;
use Skatenea\Core\Config;
use Skatenea\Infrastructure\Print\PrintWorker;

$once = in_array('--once', $argv ?? [], true);

/** @var Skatenea\Core\App $app */
$app = require dirname(__DIR__) . '/config/bootstrap.php';
$c = $app->container();
$worker = new PrintWorker(
    $c->get(PrintJobRepositoryInterface::class),
    $c->get(Config::class),
    $c->get(Monolog\Logger::class)
);

do {
    $result = $worker->process(20);
    echo sprintf("[%s] print worker: ok=%d fallidos=%d\n", gmdate('c'), $result['done'], $result['failed']);
    if ($once) {
        break;
    }
    sleep(3);
} while (true);
