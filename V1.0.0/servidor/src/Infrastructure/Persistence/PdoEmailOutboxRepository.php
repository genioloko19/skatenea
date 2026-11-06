<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Application\Mail\EmailOutboxRepositoryInterface;

final class PdoEmailOutboxRepository extends PdoRepository implements EmailOutboxRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function enqueue(array $data): void
    {
        $this->insert('email_outbox', $data);
    }

    /** @return list<array<string, mixed>> */
    public function pending(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM email_outbox
             WHERE status = \'pending\' AND scheduled_at <= :now
             ORDER BY scheduled_at ASC LIMIT ' . max(1, $limit),
            ['now' => gmdate('Y-m-d H:i:s')]
        );
    }

    public function claim(string $id): bool
    {
        return $this->execute(
            'UPDATE email_outbox SET status = \'sending\' WHERE id = :id AND status = \'pending\'',
            ['id' => $id]
        ) === 1;
    }

    public function markSent(string $id, string $sentAt): void
    {
        $this->execute(
            'UPDATE email_outbox SET status = \'sent\', sent_at = :sent_at, last_error = NULL WHERE id = :id',
            ['id' => $id, 'sent_at' => $sentAt]
        );
    }

    public function markFailed(string $id, string $error, int $attempts, string $nextAttemptAt, bool $permanent): void
    {
        $this->execute(
            'UPDATE email_outbox SET status = :status, attempts = :attempts, last_error = :error,
                scheduled_at = :next_attempt WHERE id = :id',
            [
                'id' => $id,
                'status' => $permanent ? 'failed' : 'pending',
                'attempts' => $attempts,
                'error' => mb_substr($error, 0, 2000),
                'next_attempt' => $nextAttemptAt,
            ]
        );
    }

    public function countPending(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM email_outbox WHERE status = \'pending\'');

        return (int) ($row['total'] ?? 0);
    }
}
