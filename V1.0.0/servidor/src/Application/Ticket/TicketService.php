<?php

declare(strict_types=1);

namespace Skatenea\Application\Ticket;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\Database;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Payment\PaymentRepositoryInterface;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;

final class TicketService
{
    private const PAYMENT_METHODS = ['cash', 'transfer', 'card', 'nequi', 'daviplata', 'other'];

    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly RaffleRepositoryInterface $raffles,
        private readonly BuyerRepositoryInterface $buyers,
        private readonly BuyerService $buyerService,
        private readonly PaymentRepositoryInterface $payments,
        private readonly AuditLogger $audit,
        private readonly Database $database
    ) {
    }

    /** @param array<string, mixed> $data */
    public function reserve(User $actor, string $ticketId, array $data, Request $request): Ticket
    {
        $ticket = $this->findOrFail($ticketId);
        $raffle = $this->raffles->findById($ticket->raffleId);
        if ($raffle === null || !$raffle->canSell()) {
            throw HttpException::conflict('La rifa no está disponible para venta.');
        }
        $this->assertCanOperate($actor, $ticket);
        if (!$ticket->isAvailable()) {
            throw HttpException::conflict('La boleta no está disponible.');
        }

        $hours = max(1, min(168, (int) ($data['reservation_hours'] ?? 48)));
        $now = gmdate('Y-m-d H:i:s');
        $reservedUntil = gmdate('Y-m-d H:i:s', time() + ($hours * 3600));

        $result = $this->database->transaction(function () use ($actor, $ticket, $data, $now, $reservedUntil): Ticket {
            $buyer = $this->buyerService->ensureBuyer($actor, $data);
            $ok = $this->tickets->transition($ticket->id, Ticket::STATUS_AVAILABLE, [
                'status' => Ticket::STATUS_RESERVED,
                'buyer_id' => $buyer->id,
                'reserved_at' => $now,
                'reserved_until' => $reservedUntil,
                'updated_at' => $now,
            ]);
            if (!$ok) {
                throw HttpException::conflict('La boleta fue tomada por otra operación.');
            }

            return $this->findOrFail($ticket->id);
        });

        $this->audit->record($actor, 'ticket.reserved', 'ticket', $ticket->id, $ticket->toArray(), $result->toArray(), $request);

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function pay(User $actor, string $ticketId, array $data, Request $request): Ticket
    {
        $ticket = $this->findOrFail($ticketId);
        $raffle = $this->raffles->findById($ticket->raffleId);
        if ($raffle === null) {
            throw HttpException::notFound('Rifa no encontrada.');
        }
        $this->assertCanOperate($actor, $ticket);
        if (!$ticket->isReserved()) {
            throw HttpException::conflict('Solo se pueden pagar boletas reservadas.');
        }
        if ($ticket->isReservationExpired()) {
            throw HttpException::conflict('La reserva expiró. Debe reservarse nuevamente.');
        }

        $method = (string) ($data['method'] ?? 'cash');
        if (!in_array($method, self::PAYMENT_METHODS, true)) {
            throw HttpException::unprocessable('Método de pago no permitido.');
        }
        $reference = isset($data['reference']) ? trim((string) $data['reference']) : null;
        $now = gmdate('Y-m-d H:i:s');

        $result = $this->database->transaction(function () use ($actor, $ticket, $method, $reference, $now): Ticket {
            $this->payments->create([
                'id' => Uuid::v4(),
                'ticket_id' => $ticket->id,
                'amount' => $ticket->price,
                'method' => $method,
                'reference' => $reference,
                'status' => 'confirmed',
                'confirmed_by' => $actor->id,
                'confirmed_at' => $now,
                'created_at' => $now,
            ]);

            $ok = $this->tickets->transition($ticket->id, Ticket::STATUS_RESERVED, [
                'status' => Ticket::STATUS_PAID,
                'paid_at' => $now,
                'updated_at' => $now,
            ]);
            if (!$ok) {
                throw HttpException::conflict('La boleta cambió de estado durante el pago.');
            }

            return $this->findOrFail($ticket->id);
        });

        $this->audit->record($actor, 'ticket.paid', 'ticket', $ticket->id, $ticket->toArray(), $result->toArray(), $request);

        return $result;
    }

    public function cancel(User $actor, string $ticketId, ?string $reason, Request $request): Ticket
    {
        $ticket = $this->findOrFail($ticketId);
        $this->assertCanOperate($actor, $ticket);
        if (in_array($ticket->status, [Ticket::STATUS_CANCELLED, Ticket::STATUS_WINNER], true)) {
            throw HttpException::conflict('La boleta no se puede anular.');
        }
        if ($ticket->isPaid() && !$actor->isSuperAdmin()) {
            throw HttpException::forbidden('Solo el super administrador puede anular una boleta pagada.');
        }

        $updated = $this->tickets->transition($ticket->id, $ticket->status, [
            'status' => Ticket::STATUS_CANCELLED,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        if (!$updated) {
            throw HttpException::conflict('No se pudo anular la boleta.');
        }

        $result = $this->findOrFail($ticket->id);
        $this->audit->record($actor, 'ticket.cancelled', 'ticket', $ticket->id, $ticket->toArray(), [
            'reason' => $reason,
            'status' => $result->status,
        ], $request);

        return $result;
    }

    /** @return list<Ticket> */
    public function myTickets(User $actor): array
    {
        if ($actor->role === User::ROLE_BUYER) {
            $buyer = $this->buyers->findByUserId($actor->id);

            return $buyer === null ? [] : $this->tickets->byBuyer($buyer->id);
        }
        if ($actor->role === User::ROLE_SELLER) {
            return $this->tickets->bySeller($actor->id);
        }

        return [];
    }

    /** @return array<string, mixed> */
    public function verify(string $token): array
    {
        $ticket = $this->tickets->findByToken($token);
        if ($ticket === null) {
            return ['valid' => false];
        }

        return [
            'valid' => true,
            'raffle' => $ticket->raffleName,
            'number' => $ticket->number,
            'status' => $ticket->status,
            'winner' => $ticket->status === Ticket::STATUS_WINNER,
        ];
    }

    public function findOrFail(string $id): Ticket
    {
        $ticket = $this->tickets->findById($id);
        if ($ticket === null) {
            throw HttpException::notFound('Boleta no encontrada.');
        }

        return $ticket;
    }

    private function assertCanOperate(User $actor, Ticket $ticket): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }
        if ($actor->role === User::ROLE_SELLER && $ticket->sellerId === $actor->id) {
            return;
        }

        throw HttpException::forbidden('No puedes operar esta boleta.');
    }
}
