<?php

declare(strict_types=1);

namespace Skatenea\Domain\Raffle;

final class Raffle
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    /** @param array<string, int|float> $stats */
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $prize,
        public readonly string $ticketPrice,
        public readonly int $totalTickets,
        public readonly string $status,
        public readonly string $createdBy,
        public readonly ?string $approvedBy,
        public readonly ?string $approvedAt,
        public readonly ?string $rejectionReason,
        public readonly string $startAt,
        public readonly string $endAt,
        public readonly ?string $drawSeedHash,
        public readonly ?string $drawSeed,
        public readonly ?string $winnerTicketId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $stats = []
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            isset($row['description']) ? (string) $row['description'] : null,
            (string) $row['prize'],
            (string) $row['ticket_price'],
            (int) $row['total_tickets'],
            (string) $row['status'],
            (string) $row['created_by'],
            isset($row['approved_by']) ? (string) $row['approved_by'] : null,
            isset($row['approved_at']) ? (string) $row['approved_at'] : null,
            isset($row['rejection_reason']) ? (string) $row['rejection_reason'] : null,
            (string) $row['start_at'],
            (string) $row['end_at'],
            isset($row['draw_seed_hash']) ? (string) $row['draw_seed_hash'] : null,
            isset($row['draw_seed']) ? (string) $row['draw_seed'] : null,
            isset($row['winner_ticket_id']) ? (string) $row['winner_ticket_id'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at'],
            is_array($row['stats'] ?? null) ? $row['stats'] : []
        );
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canSell(): bool
    {
        $now = time();

        return $this->isActive()
            && strtotime($this->startAt) <= $now
            && strtotime($this->endAt) >= $now;
    }

    public function belongsTo(string $userId): bool
    {
        return $this->createdBy === $userId;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'prize' => $this->prize,
            'ticket_price' => (float) $this->ticketPrice,
            'total_tickets' => $this->totalTickets,
            'status' => $this->status,
            'created_by' => $this->createdBy,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt,
            'rejection_reason' => $this->rejectionReason,
            'start_at' => $this->startAt,
            'end_at' => $this->endAt,
            'draw_seed_hash' => $this->drawSeedHash,
            'winner_ticket_id' => $this->winnerTicketId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'stats' => $this->stats,
        ];
    }
}
