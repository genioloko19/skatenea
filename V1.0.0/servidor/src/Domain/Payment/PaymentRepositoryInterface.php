<?php

declare(strict_types=1);

namespace Skatenea\Domain\Payment;

interface PaymentRepositoryInterface
{
    public function findById(string $id): ?Payment;

    /** @return list<Payment> */
    public function byTicket(string $ticketId): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): Payment;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Payment;
}
