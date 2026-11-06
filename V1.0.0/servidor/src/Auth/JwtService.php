<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Skatenea\Core\Config;
use Skatenea\Core\HttpException;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;

final class JwtService
{
    public function __construct(private readonly Config $config)
    {
        $secret = $config->string('JWT_SECRET');
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET debe tener al menos 32 caracteres.');
        }
    }

    /** @return array{token: string, expires_in: int, jti: string} */
    public function issueAccessToken(User $user): array
    {
        $now = time();
        $ttl = $this->config->int('JWT_ACCESS_TTL', 900);
        $jti = Uuid::v4();

        $payload = [
            'iss' => $this->config->string('JWT_ISSUER', 'skatenea-api'),
            'aud' => $this->config->string('JWT_AUDIENCE', 'skatenea-client'),
            'sub' => $user->id,
            'role' => $user->role,
            'jti' => $jti,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ];

        return [
            'token' => JWT::encode($payload, $this->config->string('JWT_SECRET'), 'HS256'),
            'expires_in' => $ttl,
            'jti' => $jti,
        ];
    }

    /** @return array<string, mixed> */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->string('JWT_SECRET'), 'HS256'));
        } catch (ExpiredException) {
            throw HttpException::unauthorized('El token expiró.');
        } catch (SignatureInvalidException) {
            throw HttpException::unauthorized('Firma de token inválida.');
        } catch (\Throwable) {
            throw HttpException::unauthorized('Token inválido.');
        }

        $claims = (array) $decoded;
        $issuer = $this->config->string('JWT_ISSUER', 'skatenea-api');
        $audience = $this->config->string('JWT_AUDIENCE', 'skatenea-client');

        if (($claims['iss'] ?? null) !== $issuer || ($claims['aud'] ?? null) !== $audience) {
            throw HttpException::unauthorized('Emisor o audiencia de token inválidos.');
        }
        if (!isset($claims['sub'], $claims['role'], $claims['exp'])) {
            throw HttpException::unauthorized('Token incompleto.');
        }
        if ((int) $claims['exp'] < time() - $this->config->int('JWT_LEEWAY', 30)) {
            throw HttpException::unauthorized('El token expiró.');
        }

        return $claims;
    }
}
