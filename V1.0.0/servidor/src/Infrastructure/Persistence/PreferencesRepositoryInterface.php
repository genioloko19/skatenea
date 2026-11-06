<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

interface PreferencesRepositoryInterface
{
    /** @return array<string, mixed> */
    public function get(string $userId): array;

    /** @param array<string, mixed> $data */
    public function save(string $userId, array $data): void;
}
