<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Domain\Audit\AuditRepositoryInterface;

final class PdoAuditRepository extends PdoRepository implements AuditRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function log(array $data): void
    {
        $this->insert('audit_logs', $data);
    }

    /** @param array{actor_id?: string, entity_type?: string, entity_id?: string, action?: string, limit?: int, offset?: int} $filters */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];
        foreach (['actor_id', 'entity_type', 'entity_id', 'action'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $where[] = $field . ' = :' . $field;
                $params[$field] = $filters[$field];
            }
        }

        $sql = 'SELECT * FROM audit_logs';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, (int) ($filters['limit'] ?? 100))
            . ' OFFSET ' . max(0, (int) ($filters['offset'] ?? 0));

        return $this->fetchAll($sql, $params);
    }
}
