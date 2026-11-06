<?php

declare(strict_types=1);

namespace Skatenea\Application\Print;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\Config;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\User\User;

final class PrintService
{
    public function __construct(
        private readonly PrintJobRepositoryInterface $jobs,
        private readonly RaffleRepositoryInterface $raffles,
        private readonly BuyerRepositoryInterface $buyers,
        private readonly AuditLogger $audit,
        private readonly Config $config
    ) {
    }

    /** @return array<string, mixed> */
    public function enqueue(User $actor, Ticket $ticket, Request $request): array
    {
        if (!in_array($ticket->status, ['paid', 'winner'], true)) {
            throw HttpException::conflict('Solo se imprimen boletas pagadas o ganadoras.');
        }

        $raffle = $this->raffles->findById($ticket->raffleId);
        if ($raffle === null) {
            throw HttpException::notFound('Rifa no encontrada.');
        }
        $buyer = $ticket->buyerId !== null ? $this->buyers->findById($ticket->buyerId) : null;
        $id = Uuid::v4();
        $payload = [
            'ticket_id' => $ticket->id,
            'number' => $ticket->number,
            'raffle' => $raffle->name,
            'raffle_code' => $raffle->code,
            'prize' => $raffle->prize,
            'price' => (float) $ticket->price,
            'buyer' => $buyer?->fullName,
            'seller' => $ticket->sellerName,
            'verify_url' => rtrim($this->config->string('PUBLIC_WEB_URL', 'http://localhost:5173'), '/')
                . '/verify/' . $ticket->token,
            'issued_at' => gmdate('Y-m-d H:i:s'),
        ];

        $this->jobs->enqueue([
            'id' => $id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'processed_at' => null,
        ]);
        $this->audit->record($actor, 'print.enqueued', 'print_job', $id, [], $payload, $request);

        return ['id' => $id, 'status' => 'pending'];
    }

    /** @return array<string, mixed>|null */
    public function status(string $id): ?array
    {
        return $this->jobs->find($id);
    }

    /** @return list<array<string, mixed>> */
    public function forTicket(string $ticketId): array
    {
        return $this->jobs->forTicket($ticketId);
    }
}
