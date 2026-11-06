<?php

declare(strict_types=1);

namespace Skatenea\Application\Print;

interface PrintJobRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function enqueue(array $data): void;

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;

    /** @return list<array<string, mixed>> */
    public function pending(int $limit = 20): array;

    public function claim(string $id): bool;

    public function markDone(string $id, string $at): void;

    public function markFailed(string $id, string $error, int $attempts, string $at): void;

    /** @return list<array<string, mixed>> */
    public function forTicket(string $ticketId): array;
}
