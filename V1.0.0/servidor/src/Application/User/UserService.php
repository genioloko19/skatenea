<?php

declare(strict_types=1);

namespace Skatenea\Application\User;

use Skatenea\Application\AuditLogger;
use Skatenea\Auth\PasswordPolicy;
use Skatenea\Auth\PasswordService;
use Skatenea\Auth\RefreshTokenRepositoryInterface;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly PasswordService $passwords,
        private readonly PasswordPolicy $policy,
        private readonly AuditLogger $audit
    ) {
    }

    /** @return list<User> */
    public function list(?string $role, ?string $search, int $limit, int $offset): array
    {
        return $this->users->all($role, $search, $limit, $offset);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data, Request $request): User
    {
        $this->assertSuperAdmin($actor);
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw HttpException::unprocessable('Correo inválido.');
        }
        if ($this->users->emailExists($email)) {
            throw HttpException::conflict('El correo ya está registrado.');
        }
        $role = (string) ($data['role'] ?? User::ROLE_BUYER);
        if (!in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_LEADER, User::ROLE_SELLER, User::ROLE_BUYER], true)) {
            throw HttpException::unprocessable('Rol inválido.');
        }
        $this->policy->validate((string) ($data['password'] ?? ''));

        $now = gmdate('Y-m-d H:i:s');
        $user = $this->users->create([
            'id' => Uuid::v4(),
            'email' => $email,
            'password_hash' => $this->passwords->hash((string) $data['password']),
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'phone' => $data['phone'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'must_change_password' => 1,
            'last_login_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->audit->record($actor, 'user.created', 'user', $user->id, [], $user->toPublicArray(), $request);

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data, Request $request): User
    {
        $this->assertSuperAdmin($actor);
        $user = $this->findOrFail($id);

        $payload = array_intersect_key($data, array_flip([
            'full_name', 'email', 'phone', 'document_type', 'document_number', 'role', 'status',
        ]));
        if (isset($payload['email'])) {
            $payload['email'] = mb_strtolower(trim((string) $payload['email']));
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                throw HttpException::unprocessable('Correo inválido.');
            }
            if ($this->users->emailExists($payload['email'], $id)) {
                throw HttpException::conflict('El correo ya está registrado.');
            }
        }
        if (
            isset($payload['role'])
            && !in_array($payload['role'], [User::ROLE_SUPER_ADMIN, User::ROLE_LEADER, User::ROLE_SELLER, User::ROLE_BUYER], true)
        ) {
            throw HttpException::unprocessable('Rol inválido.');
        }
        if (
            isset($payload['status'])
            && !in_array($payload['status'], [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_LOCKED], true)
        ) {
            throw HttpException::unprocessable('Estado inválido.');
        }
        if (isset($payload['role']) && $user->isSuperAdmin() && $payload['role'] !== User::ROLE_SUPER_ADMIN) {
            $this->assertNotLastSuperAdmin($user->id);
        }
        $payload['updated_at'] = gmdate('Y-m-d H:i:s');
        $updated = $this->users->update($id, $payload);

        if (isset($payload['status']) && $payload['status'] !== User::STATUS_ACTIVE) {
            $this->refreshTokens->revokeAllForUser($id, gmdate('Y-m-d H:i:s'));
        }

        $this->audit->record($actor, 'user.updated', 'user', $id, $user->toPublicArray(), $updated->toPublicArray(), $request);

        return $updated;
    }

    public function deactivate(User $actor, string $id, Request $request): User
    {
        $this->assertSuperAdmin($actor);
        if ($actor->id === $id) {
            throw HttpException::conflict('No puedes desactivar tu propia cuenta.');
        }
        $user = $this->findOrFail($id);
        $this->assertNotLastSuperAdmin($id);

        $updated = $this->users->update($id, [
            'status' => User::STATUS_INACTIVE,
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->refreshTokens->revokeAllForUser($id, gmdate('Y-m-d H:i:s'));
        $this->audit->record($actor, 'user.deactivated', 'user', $id, $user->toPublicArray(), $updated->toPublicArray(), $request);

        return $updated;
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(User $actor, array $data, Request $request): User
    {
        $payload = array_intersect_key($data, array_flip(['full_name', 'phone', 'document_type', 'document_number']));
        $payload['updated_at'] = gmdate('Y-m-d H:i:s');
        $updated = $this->users->update($actor->id, $payload);
        $this->audit->record($actor, 'profile.updated', 'user', $actor->id, $actor->toPublicArray(), $updated->toPublicArray(), $request);

        return $updated;
    }

    public function findOrFail(string $id): User
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            throw HttpException::notFound('Usuario no encontrado.');
        }

        return $user;
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (!$actor->isSuperAdmin()) {
            throw HttpException::forbidden('Solo el super administrador puede gestionar usuarios.');
        }
    }

    private function assertNotLastSuperAdmin(string $userId): void
    {
        $user = $this->users->findById($userId);
        if ($user === null || !$user->isSuperAdmin()) {
            return;
        }
        $count = 0;
        foreach ($this->users->all(User::ROLE_SUPER_ADMIN, null, 1000) as $candidate) {
            if ($candidate->isActive()) {
                $count++;
            }
        }
        if ($count <= 1) {
            throw HttpException::conflict('Debe existir al menos un super administrador activo.');
        }
    }
}
