<?php

declare(strict_types=1);

namespace Skatenea\Auth;

interface RefreshTokenRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;

    /** @return array<string, mixed>|null */
    public function findByHash(string $hash): ?array;

    public function revoke(string $id, string $at, ?string $replacedBy = null): void;

    public function revokeAllForUser(string $userId, string $at): void;

    public function deleteExpired(string $before): int;
}
