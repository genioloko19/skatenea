<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Export\ExportService;
use Skatenea\Application\Raffle\RaffleService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Raffle\RaffleRepositoryInterface;
use Skatenea\Domain\Ticket\TicketRepositoryInterface;
use Skatenea\Domain\User\User;

final class RaffleController extends BaseController
{
    public function __construct(
        private readonly RaffleService $service,
        private readonly RaffleRepositoryInterface $raffles,
        private readonly TicketRepositoryInterface $tickets,
        private readonly ExportService $exports
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->attribute('user');
        $filters = [
            'status' => (string) $request->query('status', ''),
            'search' => (string) $request->query('search', ''),
            'limit' => $this->queryInt($request, 'limit', 50, 1, 200),
            'offset' => $this->queryInt($request, 'offset', 0, 0),
        ];

        if (!$user instanceof User) {
            $filters['status'] = Raffle::STATUS_ACTIVE;
        } elseif ($user->role === User::ROLE_BUYER) {
            $filters['status'] = Raffle::STATUS_ACTIVE;
        } elseif ($user->role === User::ROLE_SELLER) {
            $filters['seller_id'] = $user->id;
        } elseif ($user->role === User::ROLE_LEADER) {
            $filters['created_by'] = $user->id;
        }

        $items = $this->raffles->all($filters);

        return Response::data([
            'raffles' => array_map(static fn (Raffle $raffle): array => $raffle->toArray(), $items),
        ], 200, [
            'count' => count($items),
            'limit' => $filters['limit'],
            'offset' => $filters['offset'],
        ]);
    }

    public function show(Request $request): Response
    {
        $raffle = $this->service->findOrFail((string) $request->param('id'));
        $user = $request->attribute('user');
        if ($user instanceof User) {
            $this->service->assertCanView($user, $raffle);
        } elseif (!$raffle->isActive()) {
            throw HttpException::notFound('Rifa no encontrada.');
        }

        return Response::data([
            'raffle' => $raffle->toArray(),
            'sellers' => $this->raffles->sellersForRaffle($raffle->id),
            'ticket_stats' => $this->tickets->countByStatus($raffle->id),
            'approvals' => $this->raffles->approvalsForRaffle($raffle->id),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->user($request);
        $data = $this->validated($request, [
            'name' => 'required|string|min:3|max:150',
            'description' => 'nullable|string|max:5000',
            'prize' => 'required|string|min:2|max:255',
            'ticket_price' => 'required|numeric|min:0',
            'total_tickets' => 'required|int|min:1|max:1000000',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'code' => 'nullable|string|max:30',
        ]);

        $raffle = $this->service->create($user, $data, $request);

        return Response::data(['raffle' => $raffle->toArray()], 201);
    }

    public function update(Request $request): Response
    {
        $user = $this->user($request);
        $data = $this->validated($request, [
            'name' => 'nullable|string|min:3|max:150',
            'description' => 'nullable|string|max:5000',
            'prize' => 'nullable|string|min:2|max:255',
            'ticket_price' => 'nullable|numeric|min:0',
            'total_tickets' => 'nullable|int|min:1|max:1000000',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'code' => 'nullable|string|max:30',
        ]);

        $raffle = $this->service->update($user, (string) $request->param('id'), $data, $request);

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function submit(Request $request): Response
    {
        $raffle = $this->service->submit($this->user($request), (string) $request->param('id'), $request);

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function approve(Request $request): Response
    {
        $data = $this->validated($request, ['reason' => 'nullable|string|max:1000']);
        $raffle = $this->service->approve(
            $this->user($request),
            (string) $request->param('id'),
            $data['reason'] ?? null,
            $request
        );

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function reject(Request $request): Response
    {
        $data = $this->validated($request, ['reason' => 'required|string|min:3|max:1000']);
        $raffle = $this->service->reject(
            $this->user($request),
            (string) $request->param('id'),
            (string) $data['reason'],
            $request
        );

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function activate(Request $request): Response
    {
        $raffle = $this->service->activate($this->user($request), (string) $request->param('id'), $request);

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function close(Request $request): Response
    {
        $raffle = $this->service->close($this->user($request), (string) $request->param('id'), $request);

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function cancel(Request $request): Response
    {
        $data = $this->validated($request, ['reason' => 'nullable|string|max:1000']);
        $raffle = $this->service->cancel(
            $this->user($request),
            (string) $request->param('id'),
            $data['reason'] ?? null,
            $request
        );

        return Response::data(['raffle' => $raffle->toArray()]);
    }

    public function assignSellers(Request $request): Response
    {
        $sellerIds = $request->input('seller_ids');
        if (!is_array($sellerIds)) {
            throw HttpException::unprocessable('seller_ids debe ser un arreglo.');
        }

        $sellers = $this->service->assignSellers(
            $this->user($request),
            (string) $request->param('id'),
            array_values(array_map('strval', $sellerIds)),
            $request
        );

        return Response::data(['sellers' => $sellers]);
    }

    public function generateTickets(Request $request): Response
    {
        $stats = $this->service->generateTickets(
            $this->user($request),
            (string) $request->param('id'),
            $request
        );

        return Response::data(['ticket_stats' => $stats], 201);
    }

    public function assignTickets(Request $request): Response
    {
        $data = $this->validated($request, [
            'seller_id' => 'required|uuid',
            'from' => 'required|int|min:1',
            'to' => 'required|int|min:1',
        ]);

        $updated = $this->service->assignTicketRange(
            $this->user($request),
            (string) $request->param('id'),
            (string) $data['seller_id'],
            (int) $data['from'],
            (int) $data['to'],
            $request
        );

        return Response::data(['updated' => $updated]);
    }

    public function draw(Request $request): Response
    {
        $result = $this->service->draw(
            $this->user($request),
            (string) $request->param('id'),
            $request
        );

        return Response::data($result);
    }

    public function approvals(Request $request): Response
    {
        $raffle = $this->service->findOrFail((string) $request->param('id'));
        $user = $this->user($request);
        $this->service->assertCanView($user, $raffle);

        return Response::data(['approvals' => $this->raffles->approvalsForRaffle($raffle->id)]);
    }

    public function verify(Request $request): Response
    {
        return Response::data([
            'verification' => $this->service->verifyTicket((string) $request->param('token')),
        ]);
    }

    public function exportTickets(Request $request): Response
    {
        $user = $this->user($request);
        $raffle = $this->service->findOrFail((string) $request->param('id'));
        $this->service->assertCanView($user, $raffle);
        $filters = [];
        if ($user->role === User::ROLE_SELLER) {
            $filters['seller_id'] = $user->id;
        }
        $tickets = $this->tickets->byRaffle($raffle->id, $filters + ['limit' => 100000]);
        $csv = $this->exports->ticketsCsv($raffle, $tickets);

        return new Response(200, $csv, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="rifa-' . $raffle->code . '.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
