<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Core\Uuid;

final class UuidTest extends TestCase
{
    public function testGeneratedUuidIsValidAndUnique(): void
    {
        $first = Uuid::v4();
        $second = Uuid::v4();

        self::assertTrue(Uuid::isValid($first));
        self::assertTrue(Uuid::isValid($second));
        self::assertNotSame($first, $second);
    }
}
