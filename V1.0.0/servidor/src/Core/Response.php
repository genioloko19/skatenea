<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status = 200,
        private readonly string $body = '',
        private readonly array $headers = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function data(array $data, int $status = 200, array $meta = []): self
    {
        $payload = ['data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $status);
    }

    /** @param array<string, string> $headers */
    public static function json(mixed $payload, int $status = 200, array $headers = []): self
    {
        $headers = array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"error":{"code":"encoding_error","message":"No se pudo serializar la respuesta."}}';
            $status = 500;
        }

        return new self($status, $json, $headers);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    /** @param array<string, mixed> $details */
    public static function error(string $code, string $message, int $status, array $details = []): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return self::json(['error' => $error], $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->status, $this->body, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        if ($this->body !== '') {
            echo $this->body;
        }
    }
}
