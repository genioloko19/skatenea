<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Skatenea\Core\Config;

final class CookieService
{
    public const REFRESH_COOKIE = 'skatenea_refresh';
    public const CSRF_COOKIE = 'skatenea_csrf';

    public function __construct(private readonly Config $config)
    {
    }

    public function setRefreshToken(string $token, int $ttl): void
    {
        $this->set(self::REFRESH_COOKIE, $token, $ttl, true);
    }

    public function setCsrfToken(string $token, int $ttl): void
    {
        $this->set(self::CSRF_COOKIE, $token, $ttl, false);
    }

    public function clear(): void
    {
        $this->set(self::REFRESH_COOKIE, '', -3600, true);
        $this->set(self::CSRF_COOKIE, '', -3600, false);
    }

    private function set(string $name, string $value, int $ttl, bool $httpOnly): void
    {
        $options = [
            'expires' => time() + $ttl,
            'path' => '/',
            'domain' => $this->config->string('COOKIE_DOMAIN'),
            'secure' => $this->config->bool('COOKIE_SECURE', false),
            'httponly' => $httpOnly,
            'samesite' => $this->config->string('COOKIE_SAMESITE', 'Lax'),
        ];

        setcookie($name, $value, $options);
    }
}
