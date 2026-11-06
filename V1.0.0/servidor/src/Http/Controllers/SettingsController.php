<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\SettingsService;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class SettingsController extends BaseController
{
    public function __construct(private readonly SettingsService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::data(['settings' => $this->service->all()]);
    }

    public function update(Request $request): Response
    {
        $settings = $this->service->update($this->user($request), $request->all(), $request);

        return Response::data(['settings' => $settings]);
    }
}
