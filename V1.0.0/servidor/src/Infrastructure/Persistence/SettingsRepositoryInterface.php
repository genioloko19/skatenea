<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

interface SettingsRepositoryInterface
{
    /** @return array<string, mixed> */
    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, ?string $updatedBy = null): void;
}
