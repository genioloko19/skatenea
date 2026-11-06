<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Auth\JwtService;
use Skatenea\Core\Config;
use Skatenea\Core\HttpException;
use Skatenea\Domain\User\User;

final class JwtServiceTest extends TestCase
{
    private function config(int $ttl = 900): Config
    {
        return new Config([
            'JWT_SECRET' => str_repeat('a', 64),
            'JWT_ISSUER' => 'skatenea-test',
            'JWT_AUDIENCE' => 'skatenea-test-client',
            'JWT_ACCESS_TTL' => (string) $ttl,
            'JWT_LEEWAY' => '0',
        ]);
    }

    private function user(): User
    {
        return new User(
            '11111111-1111-4111-8111-111111111111',
            'test@example.com',
            'hash',
            'Test User',
            User::ROLE_SELLER,
            User::STATUS_ACTIVE,
            null,
            null,
            null,
            false,
            null,
            '2026-01-01 00:00:00',
            '2026-01-01 00:00:00'
        );
    }

    public function testIssueAndDecodeToken(): void
    {
        $service = new JwtService($this->config());
        $issued = $service->issueAccessToken($this->user());
        $claims = $service->decode($issued['token']);

        self::assertSame($this->user()->id, $claims['sub']);
        self::assertSame(User::ROLE_SELLER, $claims['role']);
        self::assertSame('skatenea-test', $claims['iss']);
    }

    public function testTamperedTokenIsRejected(): void
    {
        $service = new JwtService($this->config());
        $issued = $service->issueAccessToken($this->user());
        $tampered = substr($issued['token'], 0, -2) . 'xx';

        $this->expectException(HttpException::class);
        $service->decode($tampered);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $service = new JwtService($this->config(-10));
        $issued = $service->issueAccessToken($this->user());

        $this->expectException(HttpException::class);
        $service->decode($issued['token']);
    }
}
