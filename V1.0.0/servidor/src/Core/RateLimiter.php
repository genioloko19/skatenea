<?php

declare(strict_types=1);

namespace Skatenea\Core;

final class RateLimiter
{
    public function __construct(private readonly string $storagePath)
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }
    }

    public function hit(string $key, int $max, int $windowSeconds): void
    {
        $file = $this->storagePath . '/' . hash('sha256', $key) . '.json';
        $now = time();
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            $raw = stream_get_contents($handle);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $hits = is_array($data['hits'] ?? null) ? $data['hits'] : [];
            $hits = array_values(array_filter(
                $hits,
                static fn ($timestamp): bool => is_int($timestamp) && $timestamp > ($now - $windowSeconds)
            ));

            if (count($hits) >= $max) {
                throw HttpException::tooManyRequests(
                    'Demasiadas solicitudes. Intenta de nuevo en ' . $windowSeconds . ' segundos.'
                );
            }

            $hits[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode(['hits' => $hits], JSON_THROW_ON_ERROR));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
