<?php

declare(strict_types=1);

use Skatenea\Application\PasswordReset\PasswordResetRepositoryInterface;
use Skatenea\Auth\RefreshTokenRepositoryInterface;
use Skatenea\Core\Database;

/** @var Skatenea\Core\App $app */
$app = require dirname(__DIR__) . '/config/bootstrap.php';
$c = $app->container();
$now = gmdate('Y-m-d H:i:s');
$database = $c->get(Database::class);

$refresh = $c->get(RefreshTokenRepositoryInterface::class);
$resets = $c->get(PasswordResetRepositoryInterface::class);
$expiredRefresh = $refresh->deleteExpired($now);
$expiredResets = $resets->deleteExpired($now);

$pdo = $database->pdo();
$cutoff = gmdate('Y-m-d H:i:s', time() - 86400 * 30);
$oldMail = $pdo->prepare('DELETE FROM email_outbox WHERE status IN (\'sent\', \'failed\', \'cancelled\') AND created_at < :cutoff');
$oldMail->execute(['cutoff' => $cutoff]);
$oldDeliveries = $pdo->prepare('DELETE FROM webhook_deliveries WHERE status IN (\'delivered\', \'failed\', \'cancelled\') AND created_at < :cutoff');
$oldDeliveries->execute(['cutoff' => $cutoff]);
$oldPrint = $pdo->prepare('DELETE FROM print_jobs WHERE status IN (\'done\', \'failed\', \'cancelled\') AND created_at < :cutoff');
$oldPrint->execute(['cutoff' => $cutoff]);

echo sprintf(
    "[%s] maintenance: refresh=%d resets=%d mail=%d webhooks=%d print=%d\n",
    gmdate('c'),
    $expiredRefresh,
    $expiredResets,
    $oldMail->rowCount(),
    $oldDeliveries->rowCount(),
    $oldPrint->rowCount()
);
