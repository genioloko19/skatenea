<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Auth\RefreshTokenRepositoryInterface;

final class PdoRefreshTokenRepository extends PdoRepository implements RefreshTokenRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->insert('refresh_tokens', $data);
    }

    /** @return array<string, mixed>|null */
    public function findByHash(string $hash): ?array
    {
        return $this->fetchOne('SELECT * FROM refresh_tokens WHERE token_hash = :hash LIMIT 1', ['hash' => $hash]);
    }

    public function revoke(string $id, string $at, ?string $replacedBy = null): void
    {
        $this->execute(
            'UPDATE refresh_tokens SET revoked_at = :at, replaced_by = :replaced_by WHERE id = :id AND revoked_at IS NULL',
            ['id' => $id, 'at' => $at, 'replaced_by' => $replacedBy]
        );
    }

    public function revokeAllForUser(string $userId, string $at): void
    {
        $this->execute(
            'UPDATE refresh_tokens SET revoked_at = :at WHERE user_id = :user_id AND revoked_at IS NULL',
            ['user_id' => $userId, 'at' => $at]
        );
    }

    public function deleteExpired(string $before): int
    {
        return $this->execute('DELETE FROM refresh_tokens WHERE expires_at < :before', ['before' => $before]);
    }
}
