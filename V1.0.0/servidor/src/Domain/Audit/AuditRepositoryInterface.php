<?php

declare(strict_types=1);

namespace Skatenea\Domain\Audit;

interface AuditRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function log(array $data): void;

    /**
     * @param array{actor_id?: string, entity_type?: string, entity_id?: string, action?: string, limit?: int, offset?: int} $filters
     * @return list<array<string, mixed>>
     */
    public function all(array $filters = []): array;
}
