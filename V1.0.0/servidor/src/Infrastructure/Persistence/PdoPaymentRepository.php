<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Domain\Payment\Payment;
use Skatenea\Domain\Payment\PaymentRepositoryInterface;

final class PdoPaymentRepository extends PdoRepository implements PaymentRepositoryInterface
{
    public function findById(string $id): ?Payment
    {
        $row = $this->fetchOne('SELECT * FROM payments WHERE id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : Payment::fromRow($row);
    }

    /** @return list<Payment> */
    public function byTicket(string $ticketId): array
    {
        return array_map(
            static fn (array $row): Payment => Payment::fromRow($row),
            $this->fetchAll(
                'SELECT * FROM payments WHERE ticket_id = :ticket_id ORDER BY created_at DESC',
                ['ticket_id' => $ticketId]
            )
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Payment
    {
        $this->insert('payments', $data);

        return $this->findById((string) $data['id'])
            ?? throw new \RuntimeException('No se pudo recuperar el pago creado.');
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Payment
    {
        $allowed = ['amount', 'method', 'reference', 'status', 'confirmed_by', 'confirmed_at'];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('payments', $id, $data);
        }

        return $this->findById($id)
            ?? throw new \RuntimeException('Pago no encontrado después de actualizar.');
    }
}
