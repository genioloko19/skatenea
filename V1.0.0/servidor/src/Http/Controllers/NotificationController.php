<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Notifications\NotificationService;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class NotificationController extends BaseController
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->user($request);

        return Response::data([
            'notifications' => $this->service->forUser($user->id, 50),
            'unread' => $this->service->unreadCount($user->id),
        ]);
    }

    public function markRead(Request $request): Response
    {
        $this->service->markRead((string) $request->param('id'), $this->user($request)->id);

        return Response::noContent();
    }

    public function markAllRead(Request $request): Response
    {
        $this->service->markAllRead($this->user($request)->id);

        return Response::noContent();
    }
}
