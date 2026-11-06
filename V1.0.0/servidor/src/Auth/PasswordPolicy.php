<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Skatenea\Core\Config;
use Skatenea\Core\HttpException;

final class PasswordPolicy
{
    public function __construct(private readonly Config $config)
    {
    }

    public function validate(string $password): void
    {
        $min = $this->config->int('PASSWORD_MIN_LENGTH', 12);
        $errors = [];

        if (mb_strlen($password) < $min) {
            $errors[] = 'Debe tener al menos ' . $min . ' caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Debe incluir una mayúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Debe incluir una minúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Debe incluir un número.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Debe incluir un carácter especial.';
        }

        if ($errors !== []) {
            throw HttpException::unprocessable('La contraseña no cumple la política.', ['password' => $errors]);
        }
    }
}
