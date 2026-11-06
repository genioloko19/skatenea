<?php

declare(strict_types=1);

namespace Skatenea\Domain\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return list<User> */
    public function all(?string $role = null, ?string $search = null, int $limit = 100, int $offset = 0): array;

    public function count(?string $role = null): int;

    /** @param array<string, mixed> $data */
    public function create(array $data): User;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): User;

    public function touchLastLogin(string $id, string $at): void;

    public function updatePassword(string $id, string $hash, string $at): void;

    public function emailExists(string $email, ?string $exceptId = null): bool;

    /** @return array<string, int> */
    public function countByRole(): array;
}
