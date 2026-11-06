<?php

declare(strict_types=1);

namespace Skatenea\Application\PasswordReset;

interface PasswordResetRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;

    /** @return array<string, mixed>|null */
    public function findValidByHash(string $hash, string $now): ?array;

    public function markUsed(string $id, string $usedAt): void;

    public function invalidateForUser(string $userId, string $usedAt): void;

    public function deleteExpired(string $before): int;
}
