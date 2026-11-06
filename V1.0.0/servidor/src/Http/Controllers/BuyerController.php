<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Ticket\BuyerService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\Buyer\Buyer;
use Skatenea\Domain\User\User;

final class BuyerController extends BaseController
{
    public function __construct(private readonly BuyerService $service)
    {
    }

    public function search(Request $request): Response
    {
        $term = (string) $request->query('q', '');
        $items = $this->service->search($this->user($request), $term);

        return Response::data([
            'buyers' => array_map(static fn (Buyer $buyer): array => $buyer->toArray(), $items),
        ]);
    }

    public function show(Request $request): Response
    {
        $user = $this->user($request);
        $buyer = $this->service->findOrFail((string) $request->param('id'));
        if ($user->role === User::ROLE_SELLER && $buyer->createdBy !== $user->id) {
            throw HttpException::forbidden('No puedes ver este comprador.');
        }
        if ($user->role === User::ROLE_BUYER) {
            throw HttpException::forbidden('No puedes ver este comprador.');
        }

        return Response::data(['buyer' => $buyer->toArray()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'required|string|min:3|max:150',
            'email' => 'nullable|email|max:190',
            'phone' => 'required|string|min:7|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
        ]);
        $buyer = $this->service->create($this->user($request), $data, $request);

        return Response::data(['buyer' => $buyer->toArray()], 201);
    }

    public function update(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'nullable|string|min:3|max:150',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|min:7|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
        ]);
        $buyer = $this->service->update(
            $this->user($request),
            (string) $request->param('id'),
            $data,
            $request
        );

        return Response::data(['buyer' => $buyer->toArray()]);
    }
}
