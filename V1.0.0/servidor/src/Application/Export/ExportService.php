<?php

declare(strict_types=1);

namespace Skatenea\Application\Export;

use Skatenea\Domain\Raffle\Raffle;
use Skatenea\Domain\Ticket\Ticket;

final class ExportService
{
    /** @param list<Ticket> $tickets */
    public function ticketsCsv(Raffle $raffle, array $tickets): string
    {
        $rows = [[
            'Rifa', 'Código', 'Número', 'Estado', 'Vendedor', 'Comprador',
            'Documento', 'Teléfono', 'Valor', 'Fecha venta', 'Fecha pago',
        ]];
        foreach ($tickets as $ticket) {
            $rows[] = [
                $raffle->name,
                $raffle->code,
                $ticket->number,
                $ticket->status,
                $ticket->sellerName ?? '',
                $ticket->buyerName ?? '',
                '',
                '',
                number_format((float) $ticket->price, 2, '.', ''),
                $ticket->reservedAt ?? '',
                $ticket->paidAt ?? '',
            ];
        }

        return $this->toCsv($rows);
    }

    /** @param list<array<string, mixed>> $entries */
    public function auditCsv(array $entries): string
    {
        $rows = [[
            'ID', 'Fecha', 'Actor ID', 'Tipo actor', 'Acción', 'Entidad', 'Entidad ID', 'IP', 'User agent',
        ]];
        foreach ($entries as $entry) {
            $rows[] = [
                $entry['id'] ?? '',
                $entry['created_at'] ?? '',
                $entry['actor_id'] ?? '',
                $entry['actor_type'] ?? '',
                $entry['action'] ?? '',
                $entry['entity_type'] ?? '',
                $entry['entity_id'] ?? '',
                $entry['ip'] ?? '',
                $entry['user_agent'] ?? '',
            ];
        }

        return $this->toCsv($rows);
    }

    /** @param list<list<mixed>> $rows */
    private function toCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('No se pudo generar el CSV.');
        }
        fwrite($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($stream, $row, ';');
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }
}
