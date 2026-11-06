<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Auth\PasswordPolicy;
use Skatenea\Core\Config;
use Skatenea\Core\HttpException;

final class PasswordPolicyTest extends TestCase
{
    private function policy(): PasswordPolicy
    {
        return new PasswordPolicy(new Config(['PASSWORD_MIN_LENGTH' => '12']));
    }

    public function testStrongPasswordPasses(): void
    {
        $this->policy()->validate('Skate#2026!Segura');
        self::assertTrue(true);
    }

    public function testShortPasswordFails(): void
    {
        $this->expectException(HttpException::class);
        $this->policy()->validate('Ab#1');
    }

    public function testPasswordWithoutSpecialCharFails(): void
    {
        $this->expectException(HttpException::class);
        $this->policy()->validate('Skate2026Segura');
    }
}
