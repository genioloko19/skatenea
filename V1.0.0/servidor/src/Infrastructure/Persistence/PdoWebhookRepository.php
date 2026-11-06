<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Application\Webhook\WebhookRepositoryInterface;

final class PdoWebhookRepository extends PdoRepository implements WebhookRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->insert('webhooks', $data);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM webhooks ORDER BY created_at DESC');
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM webhooks WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): void
    {
        $allowed = ['name', 'url', 'secret', 'events', 'active', 'updated_at'];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('webhooks', $id, $data);
        }
    }

    public function delete(string $id): void
    {
        $this->execute('DELETE FROM webhooks WHERE id = :id', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function active(): array
    {
        return $this->fetchAll('SELECT * FROM webhooks WHERE active = 1 ORDER BY created_at ASC');
    }

    /** @param array<string, mixed> $data */
    public function createDelivery(array $data): void
    {
        $this->insert('webhook_deliveries', $data);
    }

    /** @return list<array<string, mixed>> */
    public function pendingDeliveries(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT d.*, w.url, w.secret FROM webhook_deliveries d
             JOIN webhooks w ON w.id = d.webhook_id
             WHERE d.status = \'pending\' AND d.next_attempt_at <= :now
             ORDER BY d.next_attempt_at ASC LIMIT ' . max(1, $limit),
            ['now' => gmdate('Y-m-d H:i:s')]
        );
    }

    public function claimDelivery(string $id): bool
    {
        return $this->execute(
            'UPDATE webhook_deliveries SET status = \'pending\' WHERE id = :id AND status = \'pending\'',
            ['id' => $id]
        ) === 1;
    }

    public function markDelivered(string $id, int $responseCode, string $at): void
    {
        $this->execute(
            'UPDATE webhook_deliveries SET status = \'delivered\', response_code = :code,
                delivered_at = :at, last_error = NULL WHERE id = :id',
            ['id' => $id, 'code' => $responseCode, 'at' => $at]
        );
    }

    public function markFailed(
        string $id,
        string $error,
        int $attempts,
        string $nextAttemptAt,
        bool $permanent,
        ?int $responseCode = null
    ): void {
        $this->execute(
            'UPDATE webhook_deliveries SET status = :status, attempts = :attempts, last_error = :error,
                response_code = :code, next_attempt_at = :next_attempt WHERE id = :id',
            [
                'id' => $id,
                'status' => $permanent ? 'failed' : 'pending',
                'attempts' => $attempts,
                'error' => mb_substr($error, 0, 2000),
                'code' => $responseCode,
                'next_attempt' => $nextAttemptAt,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function deliveriesForWebhook(string $webhookId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM webhook_deliveries WHERE webhook_id = :webhook_id
             ORDER BY created_at DESC LIMIT ' . max(1, $limit),
            ['webhook_id' => $webhookId]
        );
    }
}
