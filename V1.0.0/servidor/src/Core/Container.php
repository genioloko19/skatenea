<?php

declare(strict_types=1);

namespace Skatenea\Core;

use Closure;

final class Container
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    public function set(string $id, mixed $value): void
    {
        $this->bindings[$id] = $value;
    }

    public function get(string $id): mixed
    {
        if (!array_key_exists($id, $this->bindings)) {
            throw new \RuntimeException(sprintf('Binding "%s" no registrado.', $id));
        }

        $value = $this->bindings[$id];

        return $value instanceof Closure ? $value($this) : $value;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->bindings);
    }
}
