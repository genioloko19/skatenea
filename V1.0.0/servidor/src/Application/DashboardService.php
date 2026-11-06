<?php

declare(strict_types=1);

namespace Skatenea\Application;

use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class DashboardService
{
    public function __construct(
        private readonly RaffleRepositoryInterface $raffles,
        private readonly TicketRepositoryInterface $tickets,
        private readonly UserRepositoryInterface $users,
        private readonly BuyerRepositoryInterface $buyers
    ) {
    }

    /** @return array<string, mixed> */
    public function summary(User $actor): array
    {
        return match ($actor->role) {
            User::ROLE_SUPER_ADMIN => $this->superAdminSummary(),
            User::ROLE_LEADER => $this->leaderSummary($actor),
            User::ROLE_SELLER => $this->sellerSummary($actor),
            default => $this->buyerSummary($actor),
        };
    }

    /** @return array<string, mixed> */
    private function superAdminSummary(): array
    {
        $raffles = $this->raffles->all(['limit' => 500]);
        $revenue = 0.0;
        $ticketsPaid = 0;
        foreach ($raffles as $raffle) {
            $revenue += (float) ($raffle->stats['revenue'] ?? 0);
            $ticketsPaid += (int) ($raffle->stats['paid'] ?? 0);
        }

        return [
            'role' => User::ROLE_SUPER_ADMIN,
            'metrics' => [
                'users' => array_sum($this->users->countByRole()),
                'users_by_role' => $this->users->countByRole(),
                'raffles' => array_sum($this->raffles->countByStatus()),
                'raffles_by_status' => $this->raffles->countByStatus(),
                'paid_tickets' => $ticketsPaid,
                'revenue' => $revenue,
            ],
            'recent_raffles' => array_map(static fn (Raffle $r): array => $r->toArray(), array_slice($raffles, 0, 6)),
        ];
    }

    /** @return array<string, mixed> */
    private function leaderSummary(User $actor): array
    {
        $raffles = $this->raffles->all(['created_by' => $actor->id, 'limit' => 500]);
        $revenue = 0.0;
        $paid = 0;
        foreach ($raffles as $raffle) {
            $revenue += (float) ($raffle->stats['revenue'] ?? 0);
            $paid += (int) ($raffle->stats['paid'] ?? 0);
        }
        $pending = array_values(array_filter(
            $raffles,
            static fn (Raffle $r): bool => $r->status === Raffle::STATUS_PENDING
        ));

        return [
            'role' => User::ROLE_LEADER,
            'metrics' => [
                'raffles' => count($raffles),
                'pending_approval' => count($pending),
                'paid_tickets' => $paid,
                'revenue' => $revenue,
            ],
            'recent_raffles' => array_map(static fn (Raffle $r): array => $r->toArray(), array_slice($raffles, 0, 6)),
        ];
    }

    /** @return array<string, mixed> */
    private function sellerSummary(User $actor): array
    {
        $tickets = $this->tickets->bySeller($actor->id);
        $counts = [
            'total' => count($tickets),
            'available' => 0,
            'reserved' => 0,
            'paid' => 0,
            'cancelled' => 0,
        ];
        $revenue = 0.0;
        foreach ($tickets as $ticket) {
            $counts[$ticket->status] = ($counts[$ticket->status] ?? 0) + 1;
            if ($ticket->status === Ticket::STATUS_PAID) {
                $revenue += (float) $ticket->price;
            }
        }

        return [
            'role' => User::ROLE_SELLER,
            'metrics' => array_merge($counts, ['revenue' => $revenue]),
            'recent_tickets' => array_map(static fn (Ticket $t): array => $t->toArray(), array_slice($tickets, 0, 8)),
        ];
    }

    /** @return array<string, mixed> */
    private function buyerSummary(User $actor): array
    {
        $buyer = $this->buyers->findByUserId($actor->id);
        $tickets = $buyer === null ? [] : $this->tickets->byBuyer($buyer->id);

        return [
            'role' => User::ROLE_BUYER,
            'metrics' => [
                'tickets' => count($tickets),
                'paid' => count(array_filter($tickets, static fn (Ticket $t): bool => $t->isPaid())),
            ],
            'recent_tickets' => array_map(static fn (Ticket $t): array => $t->toArray(), array_slice($tickets, 0, 8)),
        ];
    }
}
