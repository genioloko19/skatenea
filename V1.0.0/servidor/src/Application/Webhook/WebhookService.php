<?php

declare(strict_types=1);

namespace Skatenea\Application\Webhook;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\Config;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;

final class WebhookService
{
    public const EVENTS = [
        'raffle.created',
        'raffle.submitted',
        'raffle.approved',
        'raffle.rejected',
        'raffle.activated',
        'raffle.cancelled',
        'raffle.finished',
        'ticket.reserved',
        'ticket.paid',
        'ticket.cancelled',
        '*',
    ];

    public function __construct(
        private readonly WebhookRepositoryInterface $repository,
        private readonly AuditLogger $audit,
        private readonly Config $config
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(User $actor, array $data, Request $request): array
    {
        $payload = $this->validate($data);
        $id = Uuid::v4();
        $secret = 'whsec_' . bin2hex(random_bytes(24));
        $now = gmdate('Y-m-d H:i:s');

        $this->repository->create([
            'id' => $id,
            'name' => $payload['name'],
            'url' => $payload['url'],
            'secret' => $secret,
            'events' => json_encode($payload['events'], JSON_THROW_ON_ERROR),
            'active' => 1,
            'created_by' => $actor->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->audit->record($actor, 'webhook.created', 'webhook', $id, [], $payload, $request);

        return [
            'id' => $id,
            'name' => $payload['name'],
            'url' => $payload['url'],
            'events' => $payload['events'],
            'secret' => $secret,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(User $actor, string $id, array $data, Request $request): array
    {
        $webhook = $this->findOrFail($id);
        $payload = $this->validate(array_merge([
            'name' => $webhook['name'],
            'url' => $webhook['url'],
            'events' => json_decode((string) $webhook['events'], true) ?: [],
        ], $data));

        $this->repository->update($id, [
            'name' => $payload['name'],
            'url' => $payload['url'],
            'events' => json_encode($payload['events'], JSON_THROW_ON_ERROR),
            'active' => isset($data['active']) ? (int) (bool) $data['active'] : (int) $webhook['active'],
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->audit->record($actor, 'webhook.updated', 'webhook', $id, $webhook, $payload, $request);

        return $this->findOrFail($id);
    }

    public function delete(User $actor, string $id, Request $request): void
    {
        $this->findOrFail($id);
        $this->repository->delete($id);
        $this->audit->record($actor, 'webhook.deleted', 'webhook', $id, [], [], $request);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return array_map(static function (array $row): array {
            $row['events'] = json_decode((string) $row['events'], true) ?: [];
            unset($row['secret']);
            return $row;
        }, $this->repository->all());
    }

    /** @return list<array<string, mixed>> */
    public function deliveries(string $id, int $limit = 50): array
    {
        return $this->repository->deliveriesForWebhook($id, $limit);
    }

    /** @return array<string, mixed> */
    public function test(User $actor, string $id, Request $request): array
    {
        $this->findOrFail($id);
        $now = gmdate('Y-m-d H:i:s');
        $deliveryId = Uuid::v4();
        $this->repository->createDelivery([
            'id' => $deliveryId,
            'webhook_id' => $id,
            'event' => 'webhook.test',
            'payload' => json_encode([
                'event' => 'webhook.test',
                'occurred_at' => $now,
                'data' => ['message' => 'Prueba de integración SKATENEA'],
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'attempts' => 0,
            'response_code' => null,
            'last_error' => null,
            'next_attempt_at' => $now,
            'created_at' => $now,
            'delivered_at' => null,
        ]);
        $this->audit->record($actor, 'webhook.tested', 'webhook', $id, [], ['delivery_id' => $deliveryId], $request);

        return ['delivery_id' => $deliveryId, 'status' => 'pending'];
    }

    /** @param array<string, mixed> $payload */
    public function dispatch(string $event, array $payload): void
    {
        $now = gmdate('Y-m-d H:i:s');
        foreach ($this->repository->active() as $webhook) {
            $events = json_decode((string) $webhook['events'], true) ?: [];
            if (!in_array($event, $events, true) && !in_array('*', $events, true)) {
                continue;
            }

            $this->repository->createDelivery([
                'id' => Uuid::v4(),
                'webhook_id' => (string) $webhook['id'],
                'event' => $event,
                'payload' => json_encode([
                    'event' => $event,
                    'occurred_at' => $now,
                    'data' => $payload,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'status' => 'pending',
                'attempts' => 0,
                'response_code' => null,
                'last_error' => null,
                'next_attempt_at' => $now,
                'created_at' => $now,
                'delivered_at' => null,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        $events = $data['events'] ?? [];
        if ($name === '' || mb_strlen($name) > 100) {
            throw HttpException::unprocessable('El nombre del webhook es obligatorio.');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            throw HttpException::unprocessable('La URL del webhook no es válida.');
        }
        $this->assertSafeDestination($url);
        if (!is_array($events) || $events === []) {
            throw HttpException::unprocessable('Debes indicar al menos un evento.');
        }
        $invalid = array_diff($events, self::EVENTS);
        if ($invalid !== []) {
            throw HttpException::unprocessable('Eventos inválidos.', ['invalid' => array_values($invalid)]);
        }

        return ['name' => $name, 'url' => $url, 'events' => array_values(array_unique($events))];
    }

    private function assertSafeDestination(string $url): void
    {
        if ($this->config->bool('WEBHOOK_ALLOW_PRIVATE', false)) {
            return;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw HttpException::unprocessable('La URL del webhook no tiene un host válido.');
        }
        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) !== false
            ? [$host]
            : (gethostbynamel($host) ?: []);

        foreach ($ips as $ip) {
            $public = filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
            if (!$public) {
                throw HttpException::unprocessable('El webhook no puede apuntar a una dirección privada o reservada.');
            }
        }
    }

    /** @return array<string, mixed> */
    private function findOrFail(string $id): array
    {
        $webhook = $this->repository->find($id);
        if ($webhook === null) {
            throw HttpException::notFound('Webhook no encontrado.');
        }

        return $webhook;
    }
}
