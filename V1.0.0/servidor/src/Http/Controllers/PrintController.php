<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Print\PrintService;
use Skatenea\Application\Ticket\TicketService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\Ticket\Ticket;
use Skatenea\Domain\User\User;

final class PrintController extends BaseController
{
    public function __construct(
        private readonly PrintService $print,
        private readonly TicketService $tickets
    ) {
    }

    public function enqueue(Request $request): Response
    {
        $actor = $this->user($request);
        $ticket = $this->tickets->findOrFail((string) $request->param('id'));
        $this->assertCanOperate($actor, $ticket);

        return Response::data(['print_job' => $this->print->enqueue($actor, $ticket, $request)], 202);
    }

    public function status(Request $request): Response
    {
        $actor = $this->user($request);
        $job = $this->print->status((string) $request->param('id'));
        if ($job === null) {
            throw HttpException::notFound('Trabajo de impresión no encontrado.');
        }
        $ticket = $this->tickets->findOrFail((string) $job['ticket_id']);
        $this->assertCanOperate($actor, $ticket);

        return Response::data(['print_job' => $job]);
    }

    private function assertCanOperate(User $actor, Ticket $ticket): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }
        if ($actor->role === User::ROLE_SELLER && $ticket->sellerId === $actor->id) {
            return;
        }

        throw HttpException::forbidden('No puedes imprimir esta boleta.');
    }
}
