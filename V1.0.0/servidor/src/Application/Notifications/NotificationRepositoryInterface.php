<?php

declare(strict_types=1);

namespace Skatenea\Application\Notifications;

interface NotificationRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;

    /** @return list<array<string, mixed>> */
    public function forUser(string $userId, int $limit = 50): array;

    public function markRead(string $id, string $userId, string $at): void;

    public function markAllRead(string $userId, string $at): void;

    public function unreadCount(string $userId): int;
}
