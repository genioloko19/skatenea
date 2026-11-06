<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\PreferencesService;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class PreferencesController extends BaseController
{
    public function __construct(private readonly PreferencesService $service)
    {
    }

    public function show(Request $request): Response
    {
        return Response::data(['preferences' => $this->service->get($this->user($request))]);
    }

    public function update(Request $request): Response
    {
        $preferences = $this->service->save($this->user($request), $request->all());

        return Response::data(['preferences' => $preferences]);
    }
}
