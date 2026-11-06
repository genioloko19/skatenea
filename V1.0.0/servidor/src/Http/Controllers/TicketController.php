<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Raffle\RaffleService;
use Skatenea\Application\Receipt\ReceiptService;
use Skatenea\Application\Ticket\TicketService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;

final class TicketController extends BaseController
{
    public function __construct(
        private readonly TicketService $service,
        private readonly TicketRepositoryInterface $tickets,
        private readonly RaffleRepositoryInterface $raffles,
        private readonly RaffleService $raffleService,
        private readonly BuyerRepositoryInterface $buyers,
        private readonly ReceiptService $receipts
    ) {
    }

    public function byRaffle(Request $request): Response
    {
        $user = $this->user($request);
        $raffle = $this->raffleService->findOrFail((string) $request->param('id'));
        $this->raffleService->assertCanView($user, $raffle);

        $filters = [
            'status' => (string) $request->query('status', ''),
            'seller_id' => (string) $request->query('seller_id', ''),
            'limit' => $this->queryInt($request, 'limit', 1000, 1, 5000),
            'offset' => $this->queryInt($request, 'offset', 0, 0),
        ];
        if ($user->role === User::ROLE_SELLER) {
            $filters['seller_id'] = $user->id;
        }

        $items = $this->tickets->byRaffle($raffle->id, $filters);

        return Response::data([
            'tickets' => array_map(static fn (Ticket $ticket): array => $ticket->toArray(), $items),
        ], 200, ['count' => count($items)]);
    }

    public function show(Request $request): Response
    {
        $ticket = $this->service->findOrFail((string) $request->param('id'));
        $this->assertCanViewTicket($this->user($request), $ticket);

        return Response::data(['ticket' => $ticket->toArray()]);
    }

    public function receipt(Request $request): Response
    {
        $ticket = $this->service->findOrFail((string) $request->param('id'));
        $this->assertCanViewTicket($this->user($request), $ticket);
        $pdf = $this->receipts->render($ticket);

        return new Response(200, $pdf, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->receipts->fileName($ticket) . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function assertCanViewTicket(User $user, Ticket $ticket): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        if ($user->role === User::ROLE_SELLER && $ticket->sellerId === $user->id) {
            return;
        }
        if ($user->role === User::ROLE_LEADER) {
            $raffle = $this->raffles->findById($ticket->raffleId);
            if ($raffle !== null && $raffle->belongsTo($user->id)) {
                return;
            }
        }
        if ($user->role === User::ROLE_BUYER) {
            $buyer = $this->buyers->findByUserId($user->id);
            if ($buyer !== null && $ticket->buyerId === $buyer->id) {
                return;
            }
        }

        throw HttpException::forbidden('No puedes ver esta boleta.');
    }

    public function reserve(Request $request): Response
    {
        $data = $this->validated($request, [
            'buyer_id' => 'nullable|uuid',
            'full_name' => 'nullable|string|min:3|max:150',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|min:7|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
            'reservation_hours' => 'nullable|int|min:1|max:168',
        ]);

        $ticket = $this->service->reserve(
            $this->user($request),
            (string) $request->param('id'),
            $data,
            $request
        );

        return Response::data(['ticket' => $ticket->toArray()]);
    }

    public function pay(Request $request): Response
    {
        $data = $this->validated($request, [
            'method' => 'required|string|in:cash,transfer,card,nequi,daviplata,other',
            'reference' => 'nullable|string|max:100',
        ]);

        $ticket = $this->service->pay(
            $this->user($request),
            (string) $request->param('id'),
            $data,
            $request
        );

        return Response::data(['ticket' => $ticket->toArray()]);
    }

    public function cancel(Request $request): Response
    {
        $data = $this->validated($request, ['reason' => 'nullable|string|max:500']);
        $ticket = $this->service->cancel(
            $this->user($request),
            (string) $request->param('id'),
            $data['reason'] ?? null,
            $request
        );

        return Response::data(['ticket' => $ticket->toArray()]);
    }

    public function mine(Request $request): Response
    {
        $items = $this->service->myTickets($this->user($request));

        return Response::data([
            'tickets' => array_map(static fn (Ticket $ticket): array => $ticket->toArray(), $items),
        ]);
    }
}
