<?php

declare(strict_types=1);

namespace Skatenea\Domain\Payment;

final class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REFUNDED = 'refunded';

    public function __construct(
        public readonly string $id,
        public readonly string $ticketId,
        public readonly string $amount,
        public readonly string $method,
        public readonly ?string $reference,
        public readonly string $status,
        public readonly ?string $confirmedBy,
        public readonly ?string $confirmedAt,
        public readonly string $createdAt
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['ticket_id'],
            (string) $row['amount'],
            (string) $row['method'],
            isset($row['reference']) ? (string) $row['reference'] : null,
            (string) $row['status'],
            isset($row['confirmed_by']) ? (string) $row['confirmed_by'] : null,
            isset($row['confirmed_at']) ? (string) $row['confirmed_at'] : null,
            (string) $row['created_at']
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticketId,
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'status' => $this->status,
            'confirmed_by' => $this->confirmedBy,
            'confirmed_at' => $this->confirmedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
