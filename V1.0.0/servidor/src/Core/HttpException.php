<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class HttpException extends \RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }

    /** @param array<string, mixed> $details */
    public static function badRequest(string $message, array $details = []): self
    {
        return new self(400, 'bad_request', $message, $details);
    }

    public static function unauthorized(string $message = 'No autenticado.'): self
    {
        return new self(401, 'unauthorized', $message);
    }

    public static function forbidden(string $message = 'No autorizado.'): self
    {
        return new self(403, 'forbidden', $message);
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): self
    {
        return new self(404, 'not_found', $message);
    }

    /** @param array<string, mixed> $details */
    public static function conflict(string $message, array $details = []): self
    {
        return new self(409, 'conflict', $message, $details);
    }

    /** @param array<string, mixed> $details */
    public static function unprocessable(string $message, array $details = []): self
    {
        return new self(422, 'validation_error', $message, $details);
    }

    public static function tooManyRequests(string $message = 'Demasiadas solicitudes.'): self
    {
        return new self(429, 'too_many_requests', $message);
    }

    public static function locked(string $message = 'La cuenta está bloqueada temporalmente.'): self
    {
        return new self(423, 'account_locked', $message);
    }
}
