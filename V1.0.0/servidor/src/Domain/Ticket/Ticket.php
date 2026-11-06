<?php

declare(strict_types=1);

namespace Skatenea\Domain\Ticket;

final class Ticket
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_WINNER = 'winner';

    public function __construct(
        public readonly string $id,
        public readonly string $raffleId,
        public readonly int $number,
        public readonly ?string $sellerId,
        public readonly ?string $buyerId,
        public readonly string $status,
        public readonly string $price,
        public readonly string $token,
        public readonly ?string $reservedUntil,
        public readonly ?string $reservedAt,
        public readonly ?string $paidAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $buyerName = null,
        public readonly ?string $sellerName = null,
        public readonly ?string $raffleName = null
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['raffle_id'],
            (int) $row['number'],
            isset($row['seller_id']) ? (string) $row['seller_id'] : null,
            isset($row['buyer_id']) ? (string) $row['buyer_id'] : null,
            (string) $row['status'],
            (string) $row['price'],
            (string) $row['token'],
            isset($row['reserved_until']) ? (string) $row['reserved_until'] : null,
            isset($row['reserved_at']) ? (string) $row['reserved_at'] : null,
            isset($row['paid_at']) ? (string) $row['paid_at'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at'],
            isset($row['buyer_name']) ? (string) $row['buyer_name'] : null,
            isset($row['seller_name']) ? (string) $row['seller_name'] : null,
            isset($row['raffle_name']) ? (string) $row['raffle_name'] : null
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isReservationExpired(): bool
    {
        return $this->status === self::STATUS_RESERVED
            && $this->reservedUntil !== null
            && strtotime($this->reservedUntil) < time();
    }

    public function belongsToSeller(string $sellerId): bool
    {
        return $this->sellerId === $sellerId;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'raffle_id' => $this->raffleId,
            'number' => $this->number,
            'seller_id' => $this->sellerId,
            'buyer_id' => $this->buyerId,
            'status' => $this->status,
            'price' => (float) $this->price,
            'qr_token' => $this->token,
            'reserved_until' => $this->reservedUntil,
            'reserved_at' => $this->reservedAt,
            'paid_at' => $this->paidAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'buyer_name' => $this->buyerName,
            'seller_name' => $this->sellerName,
            'raffle_name' => $this->raffleName,
        ];
    }
}
