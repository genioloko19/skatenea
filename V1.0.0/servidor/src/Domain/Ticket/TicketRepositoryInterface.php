<?php

declare(strict_types=1);

namespace Skatenea\Domain\Ticket;

interface TicketRepositoryInterface
{
    public function findById(string $id): ?Ticket;

    public function findByToken(string $token): ?Ticket;

    public function findByRaffleAndNumber(string $raffleId, int $number): ?Ticket;

    /**
     * @param array{status?: string, seller_id?: string, buyer_id?: string, limit?: int, offset?: int} $filters
     * @return list<Ticket>
     */
    public function byRaffle(string $raffleId, array $filters = []): array;

    /** @return list<Ticket> */
    public function bySeller(string $sellerId, ?string $status = null): array;

    /** @return list<Ticket> */
    public function byBuyer(string $buyerId): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): Ticket;

    /**
     * @param list<array<string, mixed>> $tickets
     */
    public function createMany(array $tickets): int;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Ticket;

    /**
     * Transición condicional segura (optimistic locking).
     *
     * @param array<string, mixed> $data
     */
    public function transition(string $id, string $expectedStatus, array $data): bool;

    /** @return array<string, int> */
    public function countByStatus(string $raffleId): array;

    public function nextNumber(string $raffleId): int;

    public function assignRange(string $raffleId, string $sellerId, int $from, int $to, string $updatedAt): int;

    /** @return list<string> */
    public function paidTicketIds(string $raffleId): array;
}
