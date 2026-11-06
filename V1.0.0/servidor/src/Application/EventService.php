<?php

declare(strict_types=1);

namespace Skatenea\Application;

use Monolog\Logger;
use Skatenea\Application\Mail\MailService;
use Skatenea\Application\Notifications\NotificationService;
use Skatenea\Application\Webhook\WebhookService;
use Skatenea\Domain\Buyer\Buyer;
use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class EventService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly WebhookService $webhooks,
        private readonly MailService $mail,
        private readonly UserRepositoryInterface $users,
        private readonly Logger $logger
    ) {
    }

    public function raffleCreated(Raffle $raffle): void
    {
        $this->emit('raffle.created', $raffle->toArray());
    }

    public function raffleSubmitted(Raffle $raffle, User $actor): void
    {
        foreach ($this->users->all(User::ROLE_SUPER_ADMIN, null, 100) as $admin) {
            $this->notify($admin->id, 'Rifa pendiente de aprobación', $raffle->name . ' espera tu revisión.');
        }
        $this->emit('raffle.submitted', $raffle->toArray() + ['actor_id' => $actor->id]);
    }

    public function raffleApproved(Raffle $raffle, User $leader): void
    {
        $this->notify($leader->id, 'Rifa aprobada', $raffle->name . ' fue aprobada. Ya puedes activarla.');
        $this->queueMail(fn () => $this->mail->queueRaffleApproved($leader, $raffle->name));
        $this->emit('raffle.approved', $raffle->toArray());
    }

    public function raffleRejected(Raffle $raffle, User $leader, string $reason): void
    {
        $this->notify($leader->id, 'Rifa rechazada', $raffle->name . ': ' . $reason);
        $this->queueMail(fn () => $this->mail->queueRaffleRejected($leader, $raffle->name, $reason));
        $this->emit('raffle.rejected', $raffle->toArray() + ['reason' => $reason]);
    }

    public function raffleActivated(Raffle $raffle): void
    {
        $this->emit('raffle.activated', $raffle->toArray());
    }

    public function raffleCancelled(Raffle $raffle, ?string $reason): void
    {
        $this->emit('raffle.cancelled', $raffle->toArray() + ['reason' => $reason]);
    }

    public function raffleFinished(Raffle $raffle, ?Ticket $winner): void
    {
        $this->emit('raffle.finished', $raffle->toArray() + ['winner' => $winner?->toArray()]);
    }

    public function ticketReserved(Ticket $ticket, Raffle $raffle, Buyer $buyer, User $actor): void
    {
        $this->notify($actor->id, 'Boleta separada', 'Boleta #' . $ticket->number . ' de ' . $raffle->name . ' quedó separada.');
        $this->emit('ticket.reserved', $ticket->toArray() + [
            'raffle' => $raffle->name,
            'buyer' => $buyer->fullName,
        ]);
    }

    public function ticketPaid(Ticket $ticket, Raffle $raffle, ?Buyer $buyer, User $actor): void
    {
        $this->notify($actor->id, 'Pago registrado', 'Boleta #' . $ticket->number . ' pagada correctamente.');
        if ($buyer?->userId !== null) {
            $this->notify($buyer->userId, 'Pago confirmado', 'Tu boleta #' . $ticket->number . ' de ' . $raffle->name . ' está pagada.');
        }
        if ($buyer?->email !== null && $buyer->email !== '') {
            $this->queueMail(fn () => $this->mail->queueTicketPaid(
                (string) $buyer->email,
                $buyer->fullName,
                $raffle->name,
                $ticket->number,
                number_format((float) $ticket->price, 0, ',', '.'),
                $ticket->token
            ));
        }
        $this->emit('ticket.paid', $ticket->toArray() + ['raffle' => $raffle->name]);
    }

    public function ticketCancelled(Ticket $ticket, Raffle $raffle, ?Buyer $buyer, User $actor, ?string $reason): void
    {
        $this->notify($actor->id, 'Boleta anulada', 'Boleta #' . $ticket->number . ' de ' . $raffle->name . ' fue anulada.');
        if ($buyer?->userId !== null) {
            $this->notify($buyer->userId, 'Boleta anulada', 'La boleta #' . $ticket->number . ' fue anulada.');
        }
        $this->emit('ticket.cancelled', $ticket->toArray() + ['raffle' => $raffle->name, 'reason' => $reason]);
    }

    /** @param array<string, mixed> $payload */
    private function emit(string $event, array $payload): void
    {
        try {
            $this->webhooks->dispatch($event, $payload);
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo encolar webhook', ['event' => $event, 'exception' => $e]);
        }
    }

    private function notify(string $userId, string $title, string $body): void
    {
        try {
            $this->notifications->notify($userId, $title, $body);
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo crear notificación', ['user_id' => $userId, 'exception' => $e]);
        }
    }

    private function queueMail(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            $this->logger->error('No se pudo encolar correo', ['exception' => $e]);
        }
    }
}
