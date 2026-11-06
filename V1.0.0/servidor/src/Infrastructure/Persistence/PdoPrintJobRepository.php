<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Application\Print\PrintJobRepositoryInterface;

final class PdoPrintJobRepository extends PdoRepository implements PrintJobRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function enqueue(array $data): void
    {
        $this->insert('print_jobs', $data);
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        return $this->fetchOne('SELECT * FROM print_jobs WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return list<array<string, mixed>> */
    public function pending(int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM print_jobs WHERE status = \'pending\' ORDER BY created_at ASC LIMIT ' . max(1, $limit)
        );
    }

    public function claim(string $id): bool
    {
        return $this->execute(
            'UPDATE print_jobs SET status = \'processing\' WHERE id = :id AND status = \'pending\'',
            ['id' => $id]
        ) === 1;
    }

    public function markDone(string $id, string $at): void
    {
        $this->execute(
            'UPDATE print_jobs SET status = \'done\', processed_at = :at, last_error = NULL WHERE id = :id',
            ['id' => $id, 'at' => $at]
        );
    }

    public function markFailed(string $id, string $error, int $attempts, string $at): void
    {
        $this->execute(
            'UPDATE print_jobs SET status = :status, attempts = :attempts, last_error = :error,
                processed_at = :at WHERE id = :id',
            [
                'id' => $id,
                'status' => $attempts >= 3 ? 'failed' : 'pending',
                'attempts' => $attempts,
                'error' => mb_substr($error, 0, 2000),
                'at' => $at,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public function forTicket(string $ticketId): array
    {
        return $this->fetchAll(
            'SELECT * FROM print_jobs WHERE ticket_id = :ticket_id ORDER BY created_at DESC',
            ['ticket_id' => $ticketId]
        );
    }
}
