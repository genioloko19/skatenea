<?php

declare(strict_types=1);

namespace Skatenea\Core;

use PDO;
use PDOException;

final class Database
{
    private PDO $pdo;

    public function __construct(private readonly Config $config)
    {
        $this->pdo = $this->connect();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->config->string('DB_DRIVER', 'sqlite');
    }

    /**
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $callback($this->pdo);
            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            if ($ownsTransaction) {
                try {
                    $this->pdo->rollBack();
                } catch (\Throwable) {
                    // La transacción ya pudo haber sido cerrada por el motor.
                }
            }

            throw $e;
        }
    }

    private function connect(): PDO
    {
        $driver = $this->config->string('DB_DRIVER', 'sqlite');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            if ($driver === 'sqlite') {
                $path = $this->config->string('DB_SQLITE_PATH', '../bd/skatenea.sqlite');
                if (!str_starts_with($path, '/') && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
                    $path = dirname(__DIR__, 2) . '/' . $path;
                }
                $directory = dirname($path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }

                $pdo = new PDO('sqlite:' . $path, null, null, $options);
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->exec('PRAGMA journal_mode = WAL');
                $pdo->exec('PRAGMA busy_timeout = 5000');

                return $pdo;
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config->string('DB_HOST', '127.0.0.1'),
                $this->config->int('DB_PORT', 3306),
                $this->config->string('DB_NAME', 'skatenea'),
                $this->config->string('DB_CHARSET', 'utf8mb4')
            );

            $ca = $this->config->string('DB_SSL_CA');
            if ($ca !== '') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }

            $pdo = new PDO(
                $dsn,
                $this->config->string('DB_USER'),
                $this->config->string('DB_PASS'),
                $options
            );
            $pdo->exec("SET time_zone = '+00:00'");

            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException('No se pudo conectar a la base de datos.', 0, $e);
        }
    }
}
