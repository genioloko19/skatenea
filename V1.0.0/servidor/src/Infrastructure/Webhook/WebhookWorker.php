<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Webhook;

use Monolog\Logger;
use Skatenea\Application\Webhook\WebhookRepositoryInterface;
use Skatenea\Core\Config;

final class WebhookWorker
{
    public function __construct(
        private readonly WebhookRepositoryInterface $repository,
        private readonly Config $config,
        private readonly Logger $logger
    ) {
    }

    /** @return array{delivered: int, failed: int} */
    public function process(int $limit = 30): array
    {
        $delivered = 0;
        $failed = 0;
        $maxAttempts = $this->config->int('WEBHOOK_MAX_ATTEMPTS', 5);

        foreach ($this->repository->pendingDeliveries($limit) as $delivery) {
            $id = (string) $delivery['id'];
            if (!$this->repository->claimDelivery($id)) {
                continue;
            }

            $payload = (string) $delivery['payload'];
            $secret = (string) $delivery['secret'];
            $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            $headers = [
                'Content-Type: application/json',
                'User-Agent: SKATENEA-Webhook/1.0',
                'X-SkateNea-Event: ' . (string) $delivery['event'],
                'X-SkateNea-Delivery: ' . $id,
                'X-SkateNea-Signature: ' . $signature,
            ];

            $curl = curl_init((string) $delivery['url']);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            $response = curl_exec($curl);
            $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = $response === false ? curl_error($curl) : '';
            curl_close($curl);

            if ($code >= 200 && $code < 300) {
                $this->repository->markDelivered($id, $code, gmdate('Y-m-d H:i:s'));
                $delivered++;
                continue;
            }

            $attempts = (int) $delivery['attempts'] + 1;
            $permanent = $attempts >= $maxAttempts;
            $delay = min(3600, 60 * (2 ** min(6, $attempts)));
            $this->repository->markFailed(
                $id,
                $error !== '' ? $error : 'HTTP ' . $code,
                $attempts,
                gmdate('Y-m-d H:i:s', time() + $delay),
                $permanent,
                $code > 0 ? $code : null
            );
            $this->logger->warning('Webhook falló', [
                'delivery_id' => $id,
                'event' => $delivery['event'],
                'status' => $code,
                'attempts' => $attempts,
            ]);
            $failed++;
        }

        return ['delivered' => $delivered, 'failed' => $failed];
    }
}
