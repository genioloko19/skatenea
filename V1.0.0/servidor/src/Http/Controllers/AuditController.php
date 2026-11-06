<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Export\ExportService;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\Audit\AuditRepositoryInterface;

final class AuditController extends BaseController
{
    public function __construct(
        private readonly AuditRepositoryInterface $audit,
        private readonly ExportService $exports
    ) {
    }

    public function index(Request $request): Response
    {
        $items = $this->audit->all([
            'actor_id' => $request->query('actor_id') !== null ? (string) $request->query('actor_id') : null,
            'entity_type' => $request->query('entity_type') !== null ? (string) $request->query('entity_type') : null,
            'entity_id' => $request->query('entity_id') !== null ? (string) $request->query('entity_id') : null,
            'action' => $request->query('action') !== null ? (string) $request->query('action') : null,
            'limit' => $this->queryInt($request, 'limit', 100, 1, 500),
            'offset' => $this->queryInt($request, 'offset', 0, 0),
        ]);

        return Response::data(['audit' => $items], 200, ['count' => count($items)]);
    }

    public function export(Request $request): Response
    {
        $items = $this->audit->all([
            'actor_id' => $request->query('actor_id') !== null ? (string) $request->query('actor_id') : null,
            'entity_type' => $request->query('entity_type') !== null ? (string) $request->query('entity_type') : null,
            'entity_id' => $request->query('entity_id') !== null ? (string) $request->query('entity_id') : null,
            'action' => $request->query('action') !== null ? (string) $request->query('action') : null,
            'limit' => 10000,
            'offset' => 0,
        ]);

        return new Response(200, $this->exports->auditCsv($items), [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="auditoria-skatenea.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
