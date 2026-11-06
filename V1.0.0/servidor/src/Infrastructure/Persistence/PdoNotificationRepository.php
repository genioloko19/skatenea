<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Application\Notifications\NotificationRepositoryInterface;

final class PdoNotificationRepository extends PdoRepository implements NotificationRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->insert('notifications', $data);
    }

    /** @return list<array<string, mixed>> */
    public function forUser(string $userId, int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications WHERE user_id = :user_id
             ORDER BY created_at DESC LIMIT ' . max(1, $limit),
            ['user_id' => $userId]
        );
    }

    public function markRead(string $id, string $userId, string $at): void
    {
        $this->execute(
            'UPDATE notifications SET read_at = :at WHERE id = :id AND user_id = :user_id AND read_at IS NULL',
            ['id' => $id, 'user_id' => $userId, 'at' => $at]
        );
    }

    public function markAllRead(string $userId, string $at): void
    {
        $this->execute(
            'UPDATE notifications SET read_at = :at WHERE user_id = :user_id AND read_at IS NULL',
            ['user_id' => $userId, 'at' => $at]
        );
    }

    public function unreadCount(string $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND read_at IS NULL',
            ['user_id' => $userId]
        );

        return (int) ($row['total'] ?? 0);
    }
}
