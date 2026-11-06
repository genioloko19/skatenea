<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Auth\ApiKeyService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class ApiKeyController extends BaseController
{
    public function __construct(private readonly ApiKeyService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::data(['api_keys' => $this->service->list()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, [
            'name' => 'required|string|min:3|max:100',
            'expires_at' => 'nullable|date',
        ]);
        $scopes = $request->input('scopes');
        if (!is_array($scopes)) {
            throw HttpException::unprocessable('scopes debe ser un arreglo.');
        }

        $key = $this->service->create(
            $this->user($request),
            (string) $data['name'],
            array_values(array_map('strval', $scopes)),
            $data['expires_at'] ?? null
        );

        return Response::data(['api_key' => $key], 201);
    }

    public function revoke(Request $request): Response
    {
        $this->service->revoke($this->user($request), (string) $request->param('id'));

        return Response::noContent();
    }
}
