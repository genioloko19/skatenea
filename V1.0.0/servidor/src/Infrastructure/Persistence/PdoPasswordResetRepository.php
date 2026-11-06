<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Application\PasswordReset\PasswordResetRepositoryInterface;

final class PdoPasswordResetRepository extends PdoRepository implements PasswordResetRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->insert('password_resets', $data);
    }

    /** @return array<string, mixed>|null */
    public function findValidByHash(string $hash, string $now): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM password_resets
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > :now LIMIT 1',
            ['hash' => $hash, 'now' => $now]
        );
    }

    public function markUsed(string $id, string $usedAt): void
    {
        $this->execute(
            'UPDATE password_resets SET used_at = :used_at WHERE id = :id AND used_at IS NULL',
            ['id' => $id, 'used_at' => $usedAt]
        );
    }

    public function invalidateForUser(string $userId, string $usedAt): void
    {
        $this->execute(
            'UPDATE password_resets SET used_at = :used_at WHERE user_id = :user_id AND used_at IS NULL',
            ['user_id' => $userId, 'used_at' => $usedAt]
        );
    }

    public function deleteExpired(string $before): int
    {
        return $this->execute('DELETE FROM password_resets WHERE expires_at < :before', ['before' => $before]);
    }
}
