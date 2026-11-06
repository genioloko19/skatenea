<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Print;

use Monolog\Logger;
use Skatenea\Application\Print\PrintJobRepositoryInterface;
use Skatenea\Core\Config;

final class PrintWorker
{
    public function __construct(
        private readonly PrintJobRepositoryInterface $jobs,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /** @return array{done: int, failed: int} */
    public function process(int $limit = 20): array
    {
        $done = 0;
        $failed = 0;
        foreach ($this->jobs->pending($limit) as $job) {
            $id = (string) $job['id'];
            if (!$this->jobs->claim($id)) {
                continue;
            }

            try {
                $payload = json_decode((string) $job['payload'], true, 512, JSON_THROW_ON_ERROR);
                $this->send($this->render($payload));
                $this->jobs->markDone($id, gmdate('Y-m-d H:i:s'));
                $done++;
            } catch (\Throwable $e) {
                $attempts = (int) $job['attempts'] + 1;
                $this->jobs->markFailed($id, $e->getMessage(), $attempts, gmdate('Y-m-d H:i:s'));
                $this->logger->error('No se pudo imprimir boleta', [
                    'print_job' => $id,
                    'attempts' => $attempts,
                    'exception' => $e,
                ]);
                $failed++;
            }
        }

        return ['done' => $done, 'failed' => $failed];
    }

    /** @param array<string, mixed> $payload */
    private function render(array $payload): string
    {
        $line = str_repeat('=', 42) . "\n";
        $text = "\x1B@" . "\x1B" . "a\x01";
        $text .= $this->encode((string) ($payload['raffle'] ?? 'RIFA')) . "\n";
        $text .= "Boleta #" . ($payload['number'] ?? '') . "\n";
        $text .= $line;
        $text .= "\x1B" . "a\x00";
        $text .= 'Premio: ' . $this->encode((string) ($payload['prize'] ?? '')) . "\n";
        $text .= 'Valor: $' . number_format((float) ($payload['price'] ?? 0), 0, ',', '.') . "\n";
        $text .= 'Comprador: ' . $this->encode((string) ($payload['buyer'] ?? 'N/D')) . "\n";
        $text .= 'Vendedor: ' . $this->encode((string) ($payload['seller'] ?? 'N/D')) . "\n";
        $text .= 'Fecha: ' . ($payload['issued_at'] ?? gmdate('Y-m-d H:i')) . "\n";
        $text .= $line;
        $text .= "\x1B" . "a\x01" . "VERIFICA EN:\n";
        $text .= $this->encode((string) ($payload['verify_url'] ?? '')) . "\n";
        $text .= "SKATENEA SGR\n\n\n";
        $text .= "\x1D" . "V\x00";

        return $text;
    }

    private function send(string $data): void
    {
        $driver = $this->config->string('PRINT_DRIVER', 'file');
        if ($driver === 'null') {
            return;
        }

        if ($driver === 'network') {
            $host = $this->config->string('PRINT_HOST', '127.0.0.1');
            $port = $this->config->int('PRINT_PORT', 9100);
            $socket = @fsockopen($host, $port, $errno, $errstr, 4);
            if ($socket === false) {
                throw new \RuntimeException("No se pudo conectar a la impresora {$host}:{$port}: {$errstr}");
            }
            fwrite($socket, $data);
            fclose($socket);
            return;
        }

        if ($driver === 'windows') {
            $share = $this->config->string('PRINT_WINDOWS_SHARE', 'POS-80');
            $path = '\\\\localhost\\' . $share;
            if (@file_put_contents($path, $data) === false) {
                throw new \RuntimeException('No se pudo escribir en la impresora compartida ' . $path);
            }
            return;
        }

        $directory = dirname(__DIR__, 3) . '/storage/prints';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($directory . '/' . bin2hex(random_bytes(8)) . '.bin', $data, LOCK_EX);
    }

    private function encode(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);

        return $converted === false ? $value : $converted;
    }
}
