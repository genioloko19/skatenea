<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class Config
{
    /** @param array<string, mixed> $values */
    public function __construct(private readonly array $values)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? $default;
        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->values[$key] ?? $default;
        return is_numeric($value) ? (int) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? $default;
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /** @return list<string> */
    public function list(string $key, string $separator = ','): array
    {
        $raw = $this->string($key);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode($separator, $raw))));
    }

    public function isProduction(): bool
    {
        return $this->string('APP_ENV', 'production') === 'production';
    }

    public function isDebug(): bool
    {
        return $this->bool('APP_DEBUG', false) && !$this->isProduction();
    }
}
