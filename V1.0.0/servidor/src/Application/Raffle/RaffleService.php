<?php

declare(strict_types=1);

namespace Skatenea\Application\Raffle;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\Database;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class RaffleService
{
    public function __construct(
        private readonly RaffleRepositoryInterface $raffles,
        private readonly TicketRepositoryInterface $tickets,
        private readonly UserRepositoryInterface $users,
        private readonly AuditLogger $audit,
        private readonly Database $database
    ) {
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data, Request $request): Raffle
    {
        $this->assertCanCreate($actor);

        $now = gmdate('Y-m-d H:i:s');
        $startAt = (string) ($data['start_at'] ?? $now);
        $endAt = (string) ($data['end_at'] ?? '');
        if (strtotime($startAt) === false || strtotime($endAt) === false) {
            throw HttpException::unprocessable('Las fechas de inicio y fin son obligatorias y válidas.');
        }
        if (strtotime($endAt) <= strtotime($startAt)) {
            throw HttpException::unprocessable('La fecha de fin debe ser posterior a la de inicio.');
        }

        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        if ($code === '') {
            $code = 'SK-' . strtoupper(bin2hex(random_bytes(3)));
        }
        if ($this->raffles->findByCode($code) !== null) {
            throw HttpException::conflict('El código de rifa ya existe.');
        }

        $total = (int) ($data['total_tickets'] ?? 0);
        if ($total < 1 || $total > 1000000) {
            throw HttpException::unprocessable('El total de boletas debe estar entre 1 y 1.000.000.');
        }
        $price = (float) ($data['ticket_price'] ?? 0);
        if ($price < 0) {
            throw HttpException::unprocessable('El precio de la boleta no puede ser negativo.');
        }

        $raffle = $this->raffles->create([
            'id' => Uuid::v4(),
            'code' => $code,
            'name' => trim((string) ($data['name'] ?? '')),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'prize' => trim((string) ($data['prize'] ?? '')),
            'ticket_price' => number_format($price, 2, '.', ''),
            'total_tickets' => $total,
            'status' => Raffle::STATUS_DRAFT,
            'created_by' => $actor->id,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'draw_seed_hash' => null,
            'draw_seed' => null,
            'winner_ticket_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->audit->record($actor, 'raffle.created', 'raffle', $raffle->id, [], $raffle->toArray(), $request);

        return $raffle;
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data, Request $request): Raffle
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);

        if (!$actor->isSuperAdmin() && !$raffle->isEditable()) {
            throw HttpException::conflict('Solo se pueden editar rifas en borrador o rechazadas.');
        }

        $payload = [];
        foreach (['name', 'description', 'prize', 'start_at', 'end_at', 'code'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = trim((string) $data[$field]);
            }
        }
        if (isset($data['ticket_price'])) {
            $payload['ticket_price'] = number_format((float) $data['ticket_price'], 2, '.', '');
        }
        if (isset($data['total_tickets'])) {
            $payload['total_tickets'] = (int) $data['total_tickets'];
            if ($payload['total_tickets'] < 1) {
                throw HttpException::unprocessable('El total de boletas debe ser positivo.');
            }
            $current = $this->tickets->countByStatus($id);
            if (($current['total'] ?? 0) > 0 && $payload['total_tickets'] !== $raffle->totalTickets) {
                throw HttpException::conflict('No puedes cambiar el total de boletas después de generarlas.');
            }
        }
        if (isset($payload['code']) && $payload['code'] !== $raffle->code) {
            if ($this->raffles->findByCode($payload['code']) !== null) {
                throw HttpException::conflict('El código de rifa ya existe.');
            }
        }
        if (
            isset($payload['start_at'], $payload['end_at'])
            && strtotime($payload['end_at']) <= strtotime($payload['start_at'])
        ) {
            throw HttpException::unprocessable('La fecha de fin debe ser posterior a la de inicio.');
        }

        $payload['updated_at'] = gmdate('Y-m-d H:i:s');
        $updated = $this->raffles->update($id, $payload);
        $this->audit->record($actor, 'raffle.updated', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function submit(User $actor, string $id, Request $request): Raffle
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if (!in_array($raffle->status, [Raffle::STATUS_DRAFT, Raffle::STATUS_REJECTED], true)) {
            throw HttpException::conflict('La rifa no está en un estado enviable a aprobación.');
        }

        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_PENDING,
            'rejection_reason' => null,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->recordApproval($raffle->id, $actor->id, 'submitted');
        $this->audit->record($actor, 'raffle.submitted', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function approve(User $actor, string $id, ?string $reason, Request $request): Raffle
    {
        $this->assertSuperAdmin($actor);
        $raffle = $this->findOrFail($id);
        if ($raffle->status !== Raffle::STATUS_PENDING) {
            throw HttpException::conflict('Solo se pueden aprobar rifas pendientes.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => $now,
            'rejection_reason' => null,
            'updated_at' => $now,
        ]);
        $this->recordApproval($raffle->id, $actor->id, 'approved', $reason);
        $this->audit->record($actor, 'raffle.approved', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function reject(User $actor, string $id, string $reason, Request $request): Raffle
    {
        $this->assertSuperAdmin($actor);
        $raffle = $this->findOrFail($id);
        if ($raffle->status !== Raffle::STATUS_PENDING) {
            throw HttpException::conflict('Solo se pueden rechazar rifas pendientes.');
        }
        if (trim($reason) === '') {
            throw HttpException::unprocessable('Debes indicar el motivo del rechazo.');
        }

        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->recordApproval($raffle->id, $actor->id, 'rejected', $reason);
        $this->audit->record($actor, 'raffle.rejected', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function activate(User $actor, string $id, Request $request): Raffle
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if ($raffle->status !== Raffle::STATUS_APPROVED) {
            throw HttpException::conflict('Solo se pueden activar rifas aprobadas.');
        }

        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_ACTIVE,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->audit->record($actor, 'raffle.activated', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function close(User $actor, string $id, Request $request): Raffle
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if ($raffle->status !== Raffle::STATUS_ACTIVE) {
            throw HttpException::conflict('Solo se pueden cerrar rifas activas.');
        }

        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_CLOSED,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->audit->record($actor, 'raffle.closed', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    public function cancel(User $actor, string $id, ?string $reason, Request $request): Raffle
    {
        $this->assertSuperAdmin($actor);
        $raffle = $this->findOrFail($id);
        if (in_array($raffle->status, [Raffle::STATUS_FINISHED, Raffle::STATUS_CANCELLED], true)) {
            throw HttpException::conflict('La rifa ya está cerrada definitivamente.');
        }

        $updated = $this->raffles->update($id, [
            'status' => Raffle::STATUS_CANCELLED,
            'rejection_reason' => $reason,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->recordApproval($raffle->id, $actor->id, 'cancelled', $reason);
        $this->audit->record($actor, 'raffle.cancelled', 'raffle', $id, $raffle->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    /**
     * @param list<string> $sellerIds
     * @return list<array<string, mixed>>
     */
    public function assignSellers(User $actor, string $id, array $sellerIds, Request $request): array
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if ($sellerIds === []) {
            throw HttpException::unprocessable('Debes indicar al menos un vendedor.');
        }

        $valid = [];
        foreach (array_unique($sellerIds) as $sellerId) {
            $seller = $this->users->findById($sellerId);
            if ($seller === null || $seller->role !== User::ROLE_SELLER || !$seller->isActive()) {
                throw HttpException::unprocessable('Vendedor inválido: ' . $sellerId);
            }
            $this->raffles->assignSeller($raffle->id, $seller->id, $actor->id, gmdate('Y-m-d H:i:s'));
            $valid[] = $seller->id;
        }

        $this->audit->record($actor, 'raffle.sellers_assigned', 'raffle', $id, [], [
            'sellers' => $valid,
        ], $request);

        return $this->raffles->sellersForRaffle($id);
    }

    /** @return array<string, int> */
    public function generateTickets(User $actor, string $id, Request $request): array
    {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if (!in_array($raffle->status, [Raffle::STATUS_APPROVED, Raffle::STATUS_ACTIVE], true)) {
            throw HttpException::conflict('La rifa debe estar aprobada o activa para generar boletas.');
        }
        $existing = $this->tickets->countByStatus($id);
        if (($existing['total'] ?? 0) > 0) {
            throw HttpException::conflict('Las boletas de esta rifa ya fueron generadas.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $tickets = [];
        for ($number = 1; $number <= $raffle->totalTickets; $number++) {
            $tickets[] = [
                'id' => Uuid::v4(),
                'raffle_id' => $raffle->id,
                'number' => $number,
                'seller_id' => null,
                'buyer_id' => null,
                'status' => 'available',
                'price' => $raffle->ticketPrice,
                'token' => bin2hex(random_bytes(32)),
                'reserved_until' => null,
                'reserved_at' => null,
                'paid_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $created = $this->database->transaction(fn (): int => $this->tickets->createMany($tickets));
        $this->audit->record($actor, 'raffle.tickets_generated', 'raffle', $id, [], [
            'count' => $created,
        ], $request);

        return $this->tickets->countByStatus($id);
    }

    public function assignTicketRange(
        User $actor,
        string $id,
        string $sellerId,
        int $from,
        int $to,
        Request $request
    ): int {
        $raffle = $this->findOrFail($id);
        $this->assertCanManage($actor, $raffle);
        if ($from < 1 || $to < $from || $to > $raffle->totalTickets) {
            throw HttpException::unprocessable('El rango de boletas no es válido.');
        }
        if (!$this->raffles->isSellerAssigned($id, $sellerId)) {
            throw HttpException::unprocessable('El vendedor no está asignado a esta rifa.');
        }

        $updated = $this->tickets->assignRange($id, $sellerId, $from, $to, gmdate('Y-m-d H:i:s'));
        $this->audit->record($actor, 'tickets.assigned', 'raffle', $id, [], [
            'seller_id' => $sellerId,
            'from' => $from,
            'to' => $to,
            'updated' => $updated,
        ], $request);

        return $updated;
    }

    /** @return array<string, mixed> */
    public function draw(User $actor, string $id, Request $request): array
    {
        $this->assertSuperAdmin($actor);
        $raffle = $this->findOrFail($id);
        if (!in_array($raffle->status, [Raffle::STATUS_ACTIVE, Raffle::STATUS_CLOSED], true)) {
            throw HttpException::conflict('La rifa debe estar activa o cerrada para sortear.');
        }
        $paidIds = $this->tickets->paidTicketIds($id);
        if ($paidIds === []) {
            throw HttpException::conflict('No hay boletas pagadas para sortear.');
        }

        $seed = bin2hex(random_bytes(32));
        $seedHash = hash('sha256', $seed);
        $winnerId = $paidIds[random_int(0, count($paidIds) - 1)];

        $this->database->transaction(function () use ($id, $winnerId, $seed, $seedHash): void {
            $this->tickets->transition($winnerId, 'paid', [
                'status' => 'winner',
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->raffles->update($id, [
                'status' => Raffle::STATUS_FINISHED,
                'winner_ticket_id' => $winnerId,
                'draw_seed' => $seed,
                'draw_seed_hash' => $seedHash,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
        });

        $winner = $this->tickets->findById($winnerId);
        $this->audit->record($actor, 'raffle.drawn', 'raffle', $id, [], [
            'winner_ticket_id' => $winnerId,
            'seed_hash' => $seedHash,
        ], $request);

        return [
            'raffle_id' => $id,
            'winner' => $winner?->toArray(),
            'seed' => $seed,
            'seed_hash' => $seedHash,
            'paid_tickets' => count($paidIds),
        ];
    }

    /** @return array<string, mixed> */
    public function verifyTicket(string $token): array
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
            'winner' => $ticket->status === 'winner',
        ];
    }

    public function findOrFail(string $id): Raffle
    {
        $raffle = $this->raffles->findById($id);
        if ($raffle === null) {
            throw HttpException::notFound('Rifa no encontrada.');
        }

        return $raffle;
    }

    public function assertCanView(User $actor, Raffle $raffle): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }
        if ($actor->role === User::ROLE_LEADER && $raffle->belongsTo($actor->id)) {
            return;
        }
        if ($actor->role === User::ROLE_SELLER && $this->raffles->isSellerAssigned($raffle->id, $actor->id)) {
            return;
        }
        if ($actor->role === User::ROLE_BUYER && $raffle->isActive()) {
            return;
        }

        throw HttpException::forbidden('No puedes ver esta rifa.');
    }

    public function assertCanManage(User $actor, Raffle $raffle): void
    {
        if ($actor->isSuperAdmin() || ($actor->role === User::ROLE_LEADER && $raffle->belongsTo($actor->id))) {
            return;
        }

        throw HttpException::forbidden('No puedes administrar esta rifa.');
    }

    private function assertCanCreate(User $actor): void
    {
        if (!in_array($actor->role, [User::ROLE_SUPER_ADMIN, User::ROLE_LEADER], true)) {
            throw HttpException::forbidden('Solo líderes o super administradores pueden crear rifas.');
        }
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (!$actor->isSuperAdmin()) {
            throw HttpException::forbidden('Solo el super administrador puede realizar esta acción.');
        }
    }

    private function recordApproval(string $raffleId, string $actorId, string $action, ?string $reason = null): void
    {
        $this->raffles->recordApproval([
            'id' => Uuid::v4(),
            'raffle_id' => $raffleId,
            'actor_id' => $actorId,
            'action' => $action,
            'reason' => $reason,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
