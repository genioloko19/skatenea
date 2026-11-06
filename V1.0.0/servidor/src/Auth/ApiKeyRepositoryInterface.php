<?php

declare(strict_types=1);

namespace Skatenea\Auth;

interface ApiKeyRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;

    /** @return array<string, mixed>|null */
    public function findByPrefix(string $prefix): ?array;

    /** @return list<array<string, mixed>> */
    public function all(): array;

    public function revoke(string $id, string $at): void;

    public function touch(string $id, string $at): void;
}
