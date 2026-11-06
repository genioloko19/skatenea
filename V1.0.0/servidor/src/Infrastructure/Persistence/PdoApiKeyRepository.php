<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Auth\ApiKeyRepositoryInterface;

final class PdoApiKeyRepository extends PdoRepository implements ApiKeyRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $this->insert('api_keys', $data);
    }

    /** @return array<string, mixed>|null */
    public function findByPrefix(string $prefix): ?array
    {
        return $this->fetchOne('SELECT * FROM api_keys WHERE prefix = :prefix LIMIT 1', ['prefix' => $prefix]);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT id, name, prefix, scopes, created_by, last_used_at, expires_at, revoked_at, created_at
             FROM api_keys ORDER BY created_at DESC'
        );
    }

    public function revoke(string $id, string $at): void
    {
        $this->execute(
            'UPDATE api_keys SET revoked_at = :at WHERE id = :id AND revoked_at IS NULL',
            ['id' => $id, 'at' => $at]
        );
    }

    public function touch(string $id, string $at): void
    {
        $this->execute('UPDATE api_keys SET last_used_at = :at WHERE id = :id', ['id' => $id, 'at' => $at]);
    }
}
