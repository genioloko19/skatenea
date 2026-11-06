<?php

declare(strict_types=1);

namespace Skatenea\Application\Webhook;

interface WebhookRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): void;

    /** @return list<array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): void;

    public function delete(string $id): void;

    /** @return list<array<string, mixed>> */
    public function active(): array;

    /** @param array<string, mixed> $data */
    public function createDelivery(array $data): void;

    /** @return list<array<string, mixed>> */
    public function pendingDeliveries(int $limit = 50): array;

    public function claimDelivery(string $id): bool;

    public function markDelivered(string $id, int $responseCode, string $at): void;

    public function markFailed(string $id, string $error, int $attempts, string $nextAttemptAt, bool $permanent, ?int $responseCode = null): void;

    /** @return list<array<string, mixed>> */
    public function deliveriesForWebhook(string $webhookId, int $limit = 50): array;
}
