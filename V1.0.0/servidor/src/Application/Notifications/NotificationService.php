<?php

declare(strict_types=1);

namespace Skatenea\Application\Notifications;

use Skatenea\Core\Uuid;

final class NotificationService
{
    public function __construct(private readonly NotificationRepositoryInterface $repository)
    {
    }

    public function notify(string $userId, string $title, string $body): void
    {
        $this->repository->create([
            'id' => Uuid::v4(),
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'read_at' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function forUser(string $userId, int $limit = 50): array
    {
        return $this->repository->forUser($userId, $limit);
    }

    public function markRead(string $id, string $userId): void
    {
        $this->repository->markRead($id, $userId, gmdate('Y-m-d H:i:s'));
    }

    public function markAllRead(string $userId): void
    {
        $this->repository->markAllRead($userId, gmdate('Y-m-d H:i:s'));
    }

    public function unreadCount(string $userId): int
    {
        return $this->repository->unreadCount($userId);
    }
}
