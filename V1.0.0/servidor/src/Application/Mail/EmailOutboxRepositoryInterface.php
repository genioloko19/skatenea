<?php

declare(strict_types=1);

namespace Skatenea\Application\Mail;

interface EmailOutboxRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function enqueue(array $data): void;

    /** @return list<array<string, mixed>> */
    public function pending(int $limit = 50): array;

    public function claim(string $id): bool;

    public function markSent(string $id, string $sentAt): void;

    public function markFailed(string $id, string $error, int $attempts, string $nextAttemptAt, bool $permanent): void;

    public function countPending(): int;
}
