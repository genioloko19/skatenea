<?php

declare(strict_types=1);

use Skatenea\Core\Database;

$fresh = in_array('--fresh', $argv ?? [], true);

try {
    /** @var Skatenea\Core\App $app */
    $app = require dirname(__DIR__) . '/config/bootstrap.php';
    $database = $app->container()->get(Database::class);
    $pdo = $database->pdo();
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar: {$e->getMessage()}\n");
    exit(1);
}

$tables = [
    'print_jobs', 'webhook_deliveries', 'webhooks', 'email_outbox',
    'notifications', 'password_resets', 'user_preferences', 'settings', 'audit_logs',
    'raffle_approvals', 'payments', 'tickets', 'buyers', 'raffle_sellers', 'raffles',
    'api_keys', 'refresh_tokens', 'users',
];

if ($fresh) {
    echo "Limpiando tablas...\n";
    if ($database->driver() === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = OFF');
    } else {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    }
    foreach ($tables as $table) {
        try {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table);
        } catch (Throwable $e) {
            fwrite(STDERR, "Aviso al borrar {$table}: {$e->getMessage()}\n");
        }
    }
    if ($database->driver() === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

$schemaPath = dirname(__DIR__, 2) . '/bd/schema.sql';
if (!is_file($schemaPath)) {
    fwrite(STDERR, "No se encontró el esquema: {$schemaPath}\n");
    exit(1);
}

$sql = (string) file_get_contents($schemaPath);
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
$statements = array_filter(array_map('trim', explode(';', $sql)));
$executed = 0;

foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        $pdo->exec($statement);
        $executed++;
    } catch (Throwable $e) {
        $message = $e->getMessage();
        if (
            str_contains($message, 'already exists')
            || str_contains($message, 'Duplicate key name')
            || str_contains($message, 'duplicate column name')
            || str_contains($message, 'Duplicate column name')
        ) {
            continue;
        }
        fwrite(STDERR, "Error ejecutando sentencia:\n{$statement}\n\n{$message}\n");
        exit(1);
    }
}

// Migraciones incrementales (se ejecutan siempre; son idempotentes).
$migrationFiles = glob(dirname(__DIR__, 2) . '/bd/migrations/*.sql') ?: [];
sort($migrationFiles);
foreach ($migrationFiles as $migrationFile) {
    $migrationSql = (string) file_get_contents($migrationFile);
    $migrationSql = preg_replace('/^\s*--.*$/m', '', $migrationSql) ?? $migrationSql;
    foreach (array_filter(array_map('trim', explode(';', $migrationSql))) as $statement) {
        if ($statement === '') {
            continue;
        }
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (
                str_contains($message, 'already exists')
                || str_contains($message, 'Duplicate key name')
                || str_contains($message, 'duplicate column name')
                || str_contains($message, 'Duplicate column name')
            ) {
                continue;
            }
            fwrite(STDERR, "Error en migración {$migrationFile}:\n{$statement}\n\n{$message}\n");
            exit(1);
        }
    }
}

echo "Migración completada. Sentencias ejecutadas: {$executed}\n";
if ($fresh) {
    echo "Modo --fresh: tablas recreadas desde cero.\n";
}
