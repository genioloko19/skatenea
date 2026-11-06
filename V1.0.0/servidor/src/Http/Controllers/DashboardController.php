<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\DashboardService;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class DashboardController extends BaseController
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function summary(Request $request): Response
    {
        return Response::data(['dashboard' => $this->service->summary($this->user($request))]);
    }
}
