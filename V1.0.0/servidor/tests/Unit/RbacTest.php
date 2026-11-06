<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Auth\Rbac;
use Skatenea\Core\HttpException;
use Skatenea\Domain\User\User;

final class RbacTest extends TestCase
{
    public function testSuperAdminHasAllPermissions(): void
    {
        $rbac = new Rbac();
        foreach (Rbac::ALL as $permission) {
            self::assertTrue($rbac->can(User::ROLE_SUPER_ADMIN, $permission), $permission);
        }
    }

    public function testSellerCannotApproveRaffles(): void
    {
        $rbac = new Rbac();
        self::assertFalse($rbac->can(User::ROLE_SELLER, 'raffles.approve'));
        $this->expectException(HttpException::class);
        $rbac->assert(User::ROLE_SELLER, 'raffles.approve');
    }

    public function testBuyerCannotManageUsers(): void
    {
        $rbac = new Rbac();
        self::assertFalse($rbac->can(User::ROLE_BUYER, 'users.read'));
    }

    public function testLeaderCanCreateAndSubmitRaffles(): void
    {
        $rbac = new Rbac();
        self::assertTrue($rbac->can(User::ROLE_LEADER, 'raffles.create'));
        self::assertTrue($rbac->can(User::ROLE_LEADER, 'raffles.submit'));
    }
}
