<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;

final class PdoRaffleRepository extends PdoRepository implements RaffleRepositoryInterface
{
    public function findById(string $id): ?Raffle
    {
        $row = $this->fetchOne('SELECT * FROM raffles WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($row === null) {
            return null;
        }

        $row['stats'] = $this->statsFor($id);

        return Raffle::fromRow($row);
    }

    public function findByCode(string $code): ?Raffle
    {
        $row = $this->fetchOne('SELECT * FROM raffles WHERE code = :code LIMIT 1', ['code' => $code]);
        if ($row === null) {
            return null;
        }

        $row['stats'] = $this->statsFor((string) $row['id']);

        return Raffle::fromRow($row);
    }

    /** @param array{status?: string, created_by?: string, seller_id?: string, search?: string, limit?: int, offset?: int} $filters */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = 'r.status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['created_by']) && $filters['created_by'] !== '') {
            $where[] = 'r.created_by = :created_by';
            $params['created_by'] = $filters['created_by'];
        }
        if (isset($filters['seller_id']) && $filters['seller_id'] !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM raffle_sellers rs WHERE rs.raffle_id = r.id AND rs.seller_id = :seller_id)';
            $params['seller_id'] = $filters['seller_id'];
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $where[] = '(LOWER(r.name) LIKE :search OR LOWER(r.code) LIKE :search)';
            $params['search'] = '%' . mb_strtolower((string) $filters['search']) . '%';
        }

        $sql = 'SELECT r.* FROM raffles r';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT ' . max(1, (int) ($filters['limit'] ?? 100))
            . ' OFFSET ' . max(0, (int) ($filters['offset'] ?? 0));

        $rows = $this->fetchAll($sql, $params);
        $stats = $this->statsForMany(array_map(static fn (array $row): string => (string) $row['id'], $rows));

        return array_map(
            static function (array $row) use ($stats): Raffle {
                $row['stats'] = $stats[$row['id']] ?? [];
                return Raffle::fromRow($row);
            },
            $rows
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Raffle
    {
        $this->insert('raffles', $data);

        return $this->findById((string) $data['id'])
            ?? throw new \RuntimeException('No se pudo recuperar la rifa creada.');
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Raffle
    {
        $allowed = [
            'code', 'name', 'description', 'prize', 'ticket_price', 'total_tickets', 'status',
            'approved_by', 'approved_at', 'rejection_reason', 'start_at', 'end_at',
            'draw_seed_hash', 'draw_seed', 'winner_ticket_id', 'updated_at',
        ];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('raffles', $id, $data);
        }

        return $this->findById($id)
            ?? throw new \RuntimeException('Rifa no encontrada después de actualizar.');
    }

    public function assignSeller(string $raffleId, string $sellerId, string $assignedBy, string $at): void
    {
        if ($this->isSellerAssigned($raffleId, $sellerId)) {
            return;
        }

        $this->insert('raffle_sellers', [
            'raffle_id' => $raffleId,
            'seller_id' => $sellerId,
            'assigned_by' => $assignedBy,
            'assigned_at' => $at,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function sellersForRaffle(string $raffleId): array
    {
        return $this->fetchAll(
            'SELECT u.id, u.full_name, u.email, u.phone, u.status, rs.assigned_at
             FROM raffle_sellers rs
             JOIN users u ON u.id = rs.seller_id
             WHERE rs.raffle_id = :raffle_id
             ORDER BY u.full_name',
            ['raffle_id' => $raffleId]
        );
    }

    public function isSellerAssigned(string $raffleId, string $sellerId): bool
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM raffle_sellers WHERE raffle_id = :raffle_id AND seller_id = :seller_id',
            ['raffle_id' => $raffleId, 'seller_id' => $sellerId]
        );

        return (int) ($row['total'] ?? 0) > 0;
    }

    /** @param array<string, mixed> $data */
    public function recordApproval(array $data): void
    {
        $this->insert('raffle_approvals', $data);
    }

    /** @return list<array<string, mixed>> */
    public function approvalsForRaffle(string $raffleId): array
    {
        return $this->fetchAll(
            'SELECT a.*, u.full_name AS actor_name
             FROM raffle_approvals a
             JOIN users u ON u.id = a.actor_id
             WHERE a.raffle_id = :raffle_id
             ORDER BY a.created_at ASC',
            ['raffle_id' => $raffleId]
        );
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS total FROM raffles GROUP BY status');
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /** @return array<string, int|float> */
    private function statsFor(string $raffleId): array
    {
        $stats = $this->statsForMany([$raffleId]);

        return $stats[$raffleId] ?? [
            'total' => 0,
            'available' => 0,
            'reserved' => 0,
            'paid' => 0,
            'cancelled' => 0,
            'revenue' => 0.0,
        ];
    }

    /**
     * @param list<string> $raffleIds
     * @return array<string, array<string, int|float>>
     */
    private function statsForMany(array $raffleIds): array
    {
        if ($raffleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($raffleIds), '?'));
        $rows = $this->fetchAll(
            'SELECT raffle_id,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = \'available\' THEN 1 ELSE 0 END) AS available,
                    SUM(CASE WHEN status = \'reserved\' THEN 1 ELSE 0 END) AS reserved,
                    SUM(CASE WHEN status = \'paid\' THEN 1 ELSE 0 END) AS paid,
                    SUM(CASE WHEN status = \'cancelled\' THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN status IN (\'paid\', \'winner\') THEN price ELSE 0 END) AS revenue
             FROM tickets
             WHERE raffle_id IN (' . $placeholders . ')
             GROUP BY raffle_id',
            $raffleIds
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['raffle_id']] = [
                'total' => (int) $row['total'],
                'available' => (int) $row['available'],
                'reserved' => (int) $row['reserved'],
                'paid' => (int) $row['paid'],
                'cancelled' => (int) $row['cancelled'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        return $result;
    }
}
