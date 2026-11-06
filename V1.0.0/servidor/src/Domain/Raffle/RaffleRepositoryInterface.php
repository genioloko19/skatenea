<?php

declare(strict_types=1);

namespace Skatenea\Domain\Raffle;

interface RaffleRepositoryInterface
{
    public function findById(string $id): ?Raffle;

    public function findByCode(string $code): ?Raffle;

    /**
     * @param array{status?: string, created_by?: string, seller_id?: string, search?: string, limit?: int, offset?: int} $filters
     * @return list<Raffle>
     */
    public function all(array $filters = []): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): Raffle;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Raffle;

    public function assignSeller(string $raffleId, string $sellerId, string $assignedBy, string $at): void;

    /** @return list<array<string, mixed>> */
    public function sellersForRaffle(string $raffleId): array;

    public function isSellerAssigned(string $raffleId, string $sellerId): bool;

    /** @param array<string, mixed> $data */
    public function recordApproval(array $data): void;

    /** @return list<array<string, mixed>> */
    public function approvalsForRaffle(string $raffleId): array;

    /** @return array<string, int> */
    public function countByStatus(): array;
}
