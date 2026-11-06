<?php

declare(strict_types=1);

namespace Skatenea\Application;

use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Domain\User\User;
use Skatenea\Infrastructure\Persistence\SettingsRepositoryInterface;

final class SettingsService
{
    private const ALLOWED = [
        'app.name',
        'app.maintenance',
        'security.registration_open',
        'security.max_login_attempts',
        'raffle.reservation_hours',
        'payments.methods',
        'notifications.email_enabled',
        'ui.default_theme',
        'ui.default_mode',
    ];

    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly AuditLogger $audit
    ) {
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->settings->all();
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function update(User $actor, array $values, Request $request): array
    {
        if (!$actor->isSuperAdmin()) {
            throw HttpException::forbidden('Solo el super administrador puede cambiar la configuración.');
        }

        $unknown = array_diff(array_keys($values), self::ALLOWED);
        if ($unknown !== []) {
            throw HttpException::unprocessable('Claves de configuración no permitidas.', ['unknown' => array_values($unknown)]);
        }

        $before = $this->settings->all();
        foreach ($values as $key => $value) {
            $this->settings->set($key, $value, $actor->id);
        }
        $after = $this->settings->all();
        $this->audit->record($actor, 'settings.updated', 'settings', null, $before, $after, $request);

        return $after;
    }
}
