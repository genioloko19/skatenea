<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\Webhook\WebhookService;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class WebhookController extends BaseController
{
    public function __construct(private readonly WebhookService $service)
    {
    }

    public function index(Request $request): Response
    {
        return Response::data(['webhooks' => $this->service->all()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, [
            'name' => 'required|string|min:3|max:100',
            'url' => 'required|string|max:500',
        ]);
        $events = $request->input('events');
        if (!is_array($events)) {
            throw HttpException::unprocessable('events debe ser un arreglo.');
        }
        $webhook = $this->service->create(
            $this->user($request),
            ['name' => $data['name'], 'url' => $data['url'], 'events' => array_values(array_map('strval', $events))],
            $request
        );

        return Response::data(['webhook' => $webhook], 201);
    }

    public function update(Request $request): Response
    {
        $data = $this->validated($request, [
            'name' => 'nullable|string|min:3|max:100',
            'url' => 'nullable|string|max:500',
            'active' => 'nullable|boolean',
        ]);
        $events = $request->input('events');
        if ($events !== null && !is_array($events)) {
            throw HttpException::unprocessable('events debe ser un arreglo.');
        }
        if (is_array($events)) {
            $data['events'] = array_values(array_map('strval', $events));
        }

        $webhook = $this->service->update(
            $this->user($request),
            (string) $request->param('id'),
            $data,
            $request
        );

        return Response::data(['webhook' => $webhook]);
    }

    public function delete(Request $request): Response
    {
        $this->service->delete($this->user($request), (string) $request->param('id'), $request);

        return Response::noContent();
    }

    public function test(Request $request): Response
    {
        $result = $this->service->test(
            $this->user($request),
            (string) $request->param('id'),
            $request
        );

        return Response::data($result, 202);
    }

    public function deliveries(Request $request): Response
    {
        return Response::data([
            'deliveries' => $this->service->deliveries((string) $request->param('id'), 50),
        ]);
    }
}
