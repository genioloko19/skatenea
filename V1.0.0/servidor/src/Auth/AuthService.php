<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\Config;
use Skatenea\Core\Database;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly BuyerRepositoryInterface $buyers,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly JwtService $jwt,
        private readonly PasswordService $passwords,
        private readonly AuditLogger $audit,
        private readonly Config $config,
        private readonly Database $database
    ) {
    }

    /** @return array<string, mixed> */
    public function login(string $email, string $password, Request $request): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !$this->passwords->verify($password, $user->passwordHash)) {
            $this->audit->record(
                null,
                'auth.login_failed',
                'user',
                $user?->id,
                [],
                ['email' => $email],
                $request,
                'anonymous'
            );
            throw HttpException::unauthorized('Credenciales inválidas.');
        }
        if (!$user->isActive()) {
            $this->audit->record($user, 'auth.login_inactive', 'user', $user->id, [], [], $request);
            throw HttpException::unauthorized('La cuenta no está activa.');
        }

        if ($this->passwords->needsRehash($user->passwordHash)) {
            $this->users->updatePassword($user->id, $this->passwords->hash($password), gmdate('Y-m-d H:i:s'));
        }
        $this->users->touchLastLogin($user->id, gmdate('Y-m-d H:i:s'));

        $access = $this->jwt->issueAccessToken($user);
        $refresh = $this->issueRefreshToken($user, $request);

        $this->audit->record($user, 'auth.login', 'user', $user->id, [], [], $request);

        return [
            'access_token' => $access['token'],
            'expires_in' => $access['expires_in'],
            'refresh_token' => $refresh['token'],
            'refresh_expires_in' => $refresh['ttl'],
            'csrf_token' => $refresh['csrf'],
            'user' => $user->toPublicArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function refresh(Request $request): array
    {
        $rawToken = $request->cookie(CookieService::REFRESH_COOKIE);
        $csrf = $request->header('x-csrf-token');
        if ($rawToken === null || $rawToken === '' || $csrf === null || $csrf === '') {
            throw HttpException::unauthorized('Sesión no encontrada.');
        }

        $record = $this->refreshTokens->findByHash(hash('sha256', $rawToken));
        if ($record === null) {
            throw HttpException::unauthorized('Sesión inválida.');
        }
        if (!empty($record['revoked_at'])) {
            $this->refreshTokens->revokeAllForUser((string) $record['user_id'], gmdate('Y-m-d H:i:s'));
            throw HttpException::unauthorized('La sesión fue revocada. Inicia sesión nuevamente.');
        }
        if (strtotime((string) $record['expires_at']) < time()) {
            throw HttpException::unauthorized('La sesión expiró.');
        }
        if (!hash_equals((string) $record['csrf_hash'], hash('sha256', $csrf))) {
            throw HttpException::unauthorized('CSRF inválido.');
        }

        $user = $this->users->findById((string) $record['user_id']);
        if ($user === null || !$user->isActive()) {
            throw HttpException::unauthorized('La cuenta no está activa.');
        }

        $newRefresh = $this->issueRefreshToken($user, $request);
        $this->refreshTokens->revoke((string) $record['id'], gmdate('Y-m-d H:i:s'), $newRefresh['id']);
        $access = $this->jwt->issueAccessToken($user);

        $this->audit->record($user, 'auth.refresh', 'user', $user->id, [], [], $request);

        return [
            'access_token' => $access['token'],
            'expires_in' => $access['expires_in'],
            'refresh_token' => $newRefresh['token'],
            'refresh_expires_in' => $newRefresh['ttl'],
            'csrf_token' => $newRefresh['csrf'],
            'user' => $user->toPublicArray(),
        ];
    }

    public function logout(?string $rawToken, Request $request): void
    {
        if ($rawToken !== null && $rawToken !== '') {
            $record = $this->refreshTokens->findByHash(hash('sha256', $rawToken));
            if ($record !== null) {
                $this->refreshTokens->revoke((string) $record['id'], gmdate('Y-m-d H:i:s'));
                $user = $this->users->findById((string) $record['user_id']);
                $this->audit->record($user, 'auth.logout', 'user', $record['user_id'], [], [], $request);
            }
        }
    }

    public function authenticateAccessToken(string $token, Request $request): User
    {
        $claims = $this->jwt->decode($token);
        $user = $this->users->findById((string) $claims['sub']);
        if ($user === null || !$user->isActive()) {
            throw HttpException::unauthorized('La cuenta no está activa.');
        }

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function registerBuyer(array $data, Request $request): array
    {
        $email = mb_strtolower(trim((string) $data['email']));
        if ($this->users->emailExists($email)) {
            throw HttpException::conflict('Ya existe una cuenta con ese correo.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $userId = Uuid::v4();
        $buyerId = Uuid::v4();

        $user = $this->database->transaction(function () use ($data, $email, $now, $userId, $buyerId): User {
            $user = $this->users->create([
                'id' => $userId,
                'email' => $email,
                'password_hash' => $this->passwords->hash((string) $data['password']),
                'full_name' => trim((string) $data['full_name']),
                'role' => User::ROLE_BUYER,
                'status' => User::STATUS_ACTIVE,
                'phone' => $data['phone'] ?? null,
                'document_type' => $data['document_type'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'must_change_password' => 0,
                'last_login_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->buyers->create([
                'id' => $buyerId,
                'full_name' => $user->fullName,
                'email' => $user->email,
                'phone' => (string) ($data['phone'] ?? ''),
                'document_type' => $data['document_type'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $user;
        });

        $this->audit->record($user, 'auth.buyer_registered', 'user', $user->id, [], [], $request, 'anonymous');

        return $user->toPublicArray();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, Request $request): void
    {
        if (!$this->passwords->verify($currentPassword, $user->passwordHash)) {
            throw HttpException::unprocessable('La contraseña actual no coincide.');
        }
        if (strlen($newPassword) < $this->config->int('PASSWORD_MIN_LENGTH', 12)) {
            throw HttpException::unprocessable('La nueva contraseña es demasiado corta.');
        }

        $this->users->updatePassword($user->id, $this->passwords->hash($newPassword), gmdate('Y-m-d H:i:s'));
        $this->refreshTokens->revokeAllForUser($user->id, gmdate('Y-m-d H:i:s'));
        $this->audit->record($user, 'auth.password_changed', 'user', $user->id, [], [], $request);
    }

    /** @return array{token: string, csrf: string, id: string, ttl: int} */
    private function issueRefreshToken(User $user, Request $request): array
    {
        $ttl = $this->config->int('REFRESH_TTL', 2592000);
        $id = Uuid::v4();
        $raw = bin2hex(random_bytes(64));
        $csrf = bin2hex(random_bytes(32));

        $this->refreshTokens->create([
            'id' => $id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'csrf_hash' => hash('sha256', $csrf),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttl),
            'revoked_at' => null,
            'replaced_by' => null,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return ['token' => $raw, 'csrf' => $csrf, 'id' => $id, 'ttl' => $ttl];
    }
}
