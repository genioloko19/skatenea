<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use PDO;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;

final class PdoTicketRepository extends PdoRepository implements TicketRepositoryInterface
{
    private const SELECT = 'SELECT t.*, b.full_name AS buyer_name, s.full_name AS seller_name, r.name AS raffle_name
        FROM tickets t
        JOIN raffles r ON r.id = t.raffle_id
        LEFT JOIN buyers b ON b.id = t.buyer_id
        LEFT JOIN users s ON s.id = t.seller_id';

    public function findById(string $id): ?Ticket
    {
        $row = $this->fetchOne(self::SELECT . ' WHERE t.id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : Ticket::fromRow($row);
    }

    public function findByToken(string $token): ?Ticket
    {
        $row = $this->fetchOne(self::SELECT . ' WHERE t.token = :token LIMIT 1', ['token' => $token]);

        return $row === null ? null : Ticket::fromRow($row);
    }

    public function findByRaffleAndNumber(string $raffleId, int $number): ?Ticket
    {
        $row = $this->fetchOne(
            self::SELECT . ' WHERE t.raffle_id = :raffle_id AND t.number = :number LIMIT 1',
            ['raffle_id' => $raffleId, 'number' => $number]
        );

        return $row === null ? null : Ticket::fromRow($row);
    }

    /** @param array{status?: string, seller_id?: string, buyer_id?: string, limit?: int, offset?: int} $filters */
    public function byRaffle(string $raffleId, array $filters = []): array
    {
        $where = ['t.raffle_id = :raffle_id'];
        $params = ['raffle_id' => $raffleId];

        if (isset($filters['status']) && $filters['status'] !== '') {
            $where[] = 't.status = :status';
            $params['status'] = $filters['status'];
        }
        if (isset($filters['seller_id']) && $filters['seller_id'] !== '') {
            $where[] = 't.seller_id = :seller_id';
            $params['seller_id'] = $filters['seller_id'];
        }
        if (isset($filters['buyer_id']) && $filters['buyer_id'] !== '') {
            $where[] = 't.buyer_id = :buyer_id';
            $params['buyer_id'] = $filters['buyer_id'];
        }

        $sql = self::SELECT . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY t.number ASC LIMIT ' . max(1, (int) ($filters['limit'] ?? 1000))
            . ' OFFSET ' . max(0, (int) ($filters['offset'] ?? 0));

        return array_map(
            static fn (array $row): Ticket => Ticket::fromRow($row),
            $this->fetchAll($sql, $params)
        );
    }

    public function bySeller(string $sellerId, ?string $status = null): array
    {
        $sql = self::SELECT . ' WHERE t.seller_id = :seller_id';
        $params = ['seller_id' => $sellerId];
        if ($status !== null && $status !== '') {
            $sql .= ' AND t.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY t.number ASC';

        return array_map(
            static fn (array $row): Ticket => Ticket::fromRow($row),
            $this->fetchAll($sql, $params)
        );
    }

    public function byBuyer(string $buyerId): array
    {
        return array_map(
            static fn (array $row): Ticket => Ticket::fromRow($row),
            $this->fetchAll(
                self::SELECT . ' WHERE t.buyer_id = :buyer_id ORDER BY t.paid_at DESC, t.number ASC',
                ['buyer_id' => $buyerId]
            )
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Ticket
    {
        $this->insert('tickets', $data);

        return $this->findById((string) $data['id'])
            ?? throw new \RuntimeException('No se pudo recuperar la boleta creada.');
    }

    /** @param list<array<string, mixed>> $tickets */
    public function createMany(array $tickets): int
    {
        if ($tickets === []) {
            return 0;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO tickets (id, raffle_id, number, seller_id, buyer_id, status, price, token,
                reserved_until, reserved_at, paid_at, created_at, updated_at)
             VALUES (:id, :raffle_id, :number, :seller_id, :buyer_id, :status, :price, :token,
                :reserved_until, :reserved_at, :paid_at, :created_at, :updated_at)'
        );

        $count = 0;
        foreach ($tickets as $ticket) {
            $statement->execute($ticket);
            $count++;
        }

        return $count;
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Ticket
    {
        $allowed = [
            'seller_id', 'buyer_id', 'status', 'price', 'reserved_until', 'reserved_at',
            'paid_at', 'updated_at',
        ];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('tickets', $id, $data);
        }

        return $this->findById($id)
            ?? throw new \RuntimeException('Boleta no encontrada después de actualizar.');
    }

    /** @param array<string, mixed> $data */
    public function transition(string $id, string $expectedStatus, array $data): bool
    {
        $allowed = ['seller_id', 'buyer_id', 'status', 'reserved_until', 'reserved_at', 'paid_at', 'updated_at'];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data === []) {
            return false;
        }

        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }

        $sql = 'UPDATE tickets SET ' . implode(', ', $assignments)
            . ' WHERE id = :id AND status = :expected_status';
        $data['id'] = $id;
        $data['expected_status'] = $expectedStatus;

        return $this->execute($sql, $data) === 1;
    }

    /** @return array<string, int> */
    public function countByStatus(string $raffleId): array
    {
        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS total FROM tickets WHERE raffle_id = :raffle_id GROUP BY status',
            ['raffle_id' => $raffleId]
        );
        $result = [
            'total' => 0,
            'available' => 0,
            'reserved' => 0,
            'paid' => 0,
            'cancelled' => 0,
            'winner' => 0,
        ];
        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
            $result['total'] += (int) $row['total'];
        }

        return $result;
    }

    public function nextNumber(string $raffleId): int
    {
        $row = $this->fetchOne(
            'SELECT COALESCE(MAX(number), 0) AS max_number FROM tickets WHERE raffle_id = :raffle_id',
            ['raffle_id' => $raffleId]
        );

        return (int) ($row['max_number'] ?? 0) + 1;
    }

    public function assignRange(string $raffleId, string $sellerId, int $from, int $to, string $updatedAt): int
    {
        return $this->execute(
            'UPDATE tickets SET seller_id = :seller_id, updated_at = :updated_at
             WHERE raffle_id = :raffle_id AND number BETWEEN :from_number AND :to_number
               AND status = \'available\'',
            [
                'seller_id' => $sellerId,
                'updated_at' => $updatedAt,
                'raffle_id' => $raffleId,
                'from_number' => $from,
                'to_number' => $to,
            ]
        );
    }

    /** @return list<string> */
    public function paidTicketIds(string $raffleId): array
    {
        $rows = $this->fetchAll(
            'SELECT id FROM tickets WHERE raffle_id = :raffle_id AND status = \'paid\' ORDER BY number',
            ['raffle_id' => $raffleId]
        );

        return array_map(static fn (array $row): string => (string) $row['id'], $rows);
    }

    /** @return list<array<string, mixed>> */
    public function rawByRaffle(string $raffleId): array
    {
        return $this->fetchAll(
            'SELECT * FROM tickets WHERE raffle_id = :raffle_id ORDER BY number ASC',
            ['raffle_id' => $raffleId]
        );
    }
}
