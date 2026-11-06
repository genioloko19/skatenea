<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Skatenea\Core\HttpException;
use Skatenea\Domain\User\User;

final class Rbac
{
    public const ALL = [
        'public.read',
        'dashboard.read',
        'profile.update',
        'raffles.read',
        'raffles.create',
        'raffles.update',
        'raffles.submit',
        'raffles.approve',
        'raffles.activate',
        'raffles.close',
        'raffles.delete',
        'tickets.read',
        'tickets.assign',
        'tickets.reserve',
        'tickets.sell',
        'tickets.pay',
        'tickets.cancel',
        'buyers.read',
        'buyers.create',
        'buyers.update',
        'users.read',
        'users.create',
        'users.update',
        'users.deactivate',
        'api_keys.read',
        'api_keys.create',
        'api_keys.revoke',
        'webhooks.read',
        'webhooks.manage',
        'audit.read',
        'settings.read',
        'settings.update',
        'print.enqueue',
    ];

    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        User::ROLE_SUPER_ADMIN => self::ALL,
        User::ROLE_LEADER => [
            'public.read',
            'dashboard.read',
            'profile.update',
            'raffles.read',
            'raffles.create',
            'raffles.update',
            'raffles.submit',
            'raffles.activate',
            'raffles.close',
            'tickets.read',
            'tickets.assign',
            'buyers.read',
            'buyers.create',
            'buyers.update',
        ],
        User::ROLE_SELLER => [
            'public.read',
            'dashboard.read',
            'profile.update',
            'raffles.read',
            'tickets.read',
            'tickets.reserve',
            'tickets.sell',
            'tickets.pay',
            'print.enqueue',
            'buyers.read',
            'buyers.create',
            'buyers.update',
        ],
        User::ROLE_BUYER => [
            'public.read',
            'dashboard.read',
            'profile.update',
            'raffles.read',
            'tickets.read',
        ],
    ];

    public function can(string $role, string $permission): bool
    {
        return in_array($permission, self::ROLE_PERMISSIONS[$role] ?? [], true);
    }

    public function assert(string $role, string $permission): void
    {
        if (!$this->can($role, $permission)) {
            throw HttpException::forbidden('Tu rol no tiene el permiso requerido: ' . $permission);
        }
    }

    /** @return list<string> */
    public function permissionsFor(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}
