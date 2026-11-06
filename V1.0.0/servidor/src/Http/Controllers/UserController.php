<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\User\UserService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;
use Skatenea\Domain\User\User;

final class UserController extends BaseController
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(Request $request): Response
    {
        $items = $this->service->list(
            $request->query('role') !== null ? (string) $request->query('role') : null,
            $request->query('search') !== null ? (string) $request->query('search') : null,
            $this->queryInt($request, 'limit', 100, 1, 500),
            $this->queryInt($request, 'offset', 0, 0)
        );

        return Response::data([
            'users' => array_map(static fn (User $user): array => $user->toPublicArray(), $items),
        ], 200, ['count' => count($items)]);
    }

    public function show(Request $request): Response
    {
        $actor = $this->user($request);
        $id = (string) $request->param('id');
        if (!$actor->isSuperAdmin() && $actor->id !== $id) {
            throw HttpException::forbidden('No puedes ver este usuario.');
        }

        return Response::data(['user' => $this->service->findOrFail($id)->toPublicArray()]);
    }

    public function sellers(Request $request): Response
    {
        $items = $this->service->list(User::ROLE_SELLER, null, 500, 0);

        return Response::data([
            'users' => array_map(static fn (User $user): array => $user->toPublicArray(), $items),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'required|string|min:3|max:150',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:12|max:255',
            'role' => 'required|string|in:super_admin,leader,seller,buyer',
            'phone' => 'nullable|string|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
        ]);
        $user = $this->service->create($this->user($request), $data, $request);

        return Response::data(['user' => $user->toPublicArray()], 201);
    }

    public function update(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'nullable|string|min:3|max:150',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
            'role' => 'nullable|string|in:super_admin,leader,seller,buyer',
            'status' => 'nullable|string|in:active,inactive,locked',
        ]);
        $user = $this->service->update(
            $this->user($request),
            (string) $request->param('id'),
            $data,
            $request
        );

        return Response::data(['user' => $user->toPublicArray()]);
    }

    public function deactivate(Request $request): Response
    {
        $user = $this->service->deactivate(
            $this->user($request),
            (string) $request->param('id'),
            $request
        );

        return Response::data(['user' => $user->toPublicArray()]);
    }

    public function updateProfile(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'nullable|string|min:3|max:150',
            'phone' => 'nullable|string|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
        ]);
        $user = $this->service->updateProfile($this->user($request), $data, $request);

        return Response::data(['user' => $user->toPublicArray()]);
    }
}
