<?php

declare(strict_types=1);

namespace Skatenea\Application;

use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Audit\AuditRepositoryInterface;
use Skatenea\Domain\User\User;

final class AuditLogger
{
    public function __construct(private readonly AuditRepositoryInterface $audit)
    {
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function record(
        ?User $actor,
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $before = [],
        array $after = [],
        ?Request $request = null,
        string $actorType = 'user'
    ): void {
        $this->audit->log([
            'id' => Uuid::v4(),
            'actor_id' => $actor?->id,
            'actor_type' => $actorType,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_json' => $before === [] ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'after_json' => $after === [] ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
