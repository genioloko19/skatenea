<?php

declare(strict_types=1);

namespace Skatenea\Application\PasswordReset;

use Skatenea\Application\AuditLogger;
use Skatenea\Application\Mail\MailService;
use Skatenea\Auth\PasswordPolicy;
use Skatenea\Auth\PasswordService;
use Skatenea\Auth\RefreshTokenRepositoryInterface;
use Skatenea\Core\Config;
use Skatenea\Core\Database;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class PasswordResetService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordResetRepositoryInterface $resets,
        private readonly PasswordService $passwords,
        private readonly PasswordPolicy $policy,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly MailService $mail,
        private readonly AuditLogger $audit,
        private readonly Database $database,
        private readonly Config $config
    ) {
    }

    public function request(string $email, Request $request): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->isActive()) {
            // Respuesta genérica: no revelamos si la cuenta existe.
            return;
        }

        $token = bin2hex(random_bytes(32));
        $now = gmdate('Y-m-d H:i:s');
        $minutes = $this->config->int('PASSWORD_RESET_TTL_MINUTES', 30);

        $this->resets->invalidateForUser($user->id, $now);
        $this->resets->create([
            'id' => Uuid::v4(),
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + ($minutes * 60)),
            'used_at' => null,
            'created_at' => $now,
        ]);
        $this->mail->queuePasswordReset($user, $token, $minutes);
        $this->audit->record($user, 'auth.password_reset_requested', 'user', $user->id, [], [], $request, 'anonymous');
    }

    public function reset(string $token, string $newPassword, Request $request): User
    {
        $this->policy->validate($newPassword);
        $now = gmdate('Y-m-d H:i:s');
        $record = $this->resets->findValidByHash(hash('sha256', $token), $now);
        if ($record === null) {
            throw HttpException::unprocessable('El enlace de recuperación es inválido o expiró.');
        }

        $user = $this->users->findById((string) $record['user_id']);
        if ($user === null || !$user->isActive()) {
            throw HttpException::unprocessable('La cuenta no está disponible.');
        }

        $this->database->transaction(function () use ($user, $record, $newPassword, $now): void {
            $this->users->updatePassword($user->id, $this->passwords->hash($newPassword), $now);
            $this->users->resetFailedLogin($user->id, $now);
            $this->resets->invalidateForUser($user->id, $now);
            $this->resets->markUsed((string) $record['id'], $now);
            $this->refreshTokens->revokeAllForUser($user->id, $now);
        });

        $this->audit->record($user, 'auth.password_reset_completed', 'user', $user->id, [], [], $request, 'anonymous');

        return $user;
    }
}
