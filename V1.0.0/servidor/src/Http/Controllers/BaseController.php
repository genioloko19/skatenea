<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Validator;
use Skatenea\Domain\User\User;

abstract class BaseController
{
    protected function user(Request $request): User
    {
        $user = $request->attribute('user');
        if (!$user instanceof User) {
            throw HttpException::unauthorized();
        }

        return $user;
    }

    /**
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    protected function validated(Request $request, array $rules): array
    {
        return Validator::validate($request->all(), $rules);
    }

    protected function queryInt(Request $request, string $key, int $default, int $min = 0, int $max = 1000000): int
    {
        $value = $request->query($key, $default);

        return max($min, min($max, (int) $value));
    }
}
