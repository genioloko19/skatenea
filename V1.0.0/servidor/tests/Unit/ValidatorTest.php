<?php

declare(strict_types=1);

namespace Skatenea\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skatenea\Core\HttpException;
use Skatenea\Core\Validator;

final class ValidatorTest extends TestCase
{
    public function testValidDataReturnsCleanArray(): void
    {
        $result = Validator::validate([
            'name' => 'Rifa de prueba',
            'email' => 'test@example.com',
            'phone' => '3001234567',
            'amount' => '10000',
        ], [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|min:7|max:30',
            'amount' => 'required|numeric|min:1',
        ]);

        self::assertSame('Rifa de prueba', $result['name']);
        self::assertSame('test@example.com', $result['email']);
        self::assertSame('3001234567', $result['phone']);
        self::assertSame(10000.0, $result['amount']);
    }

    public function testNumericStringsAreValidatedAsTextForPhone(): void
    {
        $result = Validator::validate(
            ['phone' => '3111111111'],
            ['phone' => 'required|string|min:7|max:30']
        );

        self::assertSame('3111111111', $result['phone']);
    }

    public function testMissingRequiredFieldThrows(): void
    {
        $this->expectException(HttpException::class);
        Validator::validate([], ['name' => 'required|string']);
    }

    public function testInvalidEmailThrows(): void
    {
        $this->expectException(HttpException::class);
        Validator::validate(['email' => 'no-es-correo'], ['email' => 'required|email']);
    }

    public function testInvalidEnumThrows(): void
    {
        $this->expectException(HttpException::class);
        Validator::validate(['role' => 'root'], ['role' => 'required|in:admin,user']);
    }

    public function testInvalidUuidThrows(): void
    {
        $this->expectException(HttpException::class);
        Validator::validate(['id' => '123'], ['id' => 'required|uuid']);
    }
}
