<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Domain\User\User;
use Skatenea\Domain\User\UserRepositoryInterface;

final class PdoUserRepository extends PdoRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $row = $this->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->fetchOne(
            'SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1',
            ['email' => trim($email)]
        );

        return $row === null ? null : User::fromRow($row);
    }

    public function all(?string $role = null, ?string $search = null, int $limit = 100, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if ($role !== null) {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }
        if ($search !== null && $search !== '') {
            $where[] = '(LOWER(full_name) LIKE :search OR LOWER(email) LIKE :search)';
            $params['search'] = '%' . mb_strtolower($search) . '%';
        }

        $sql = 'SELECT * FROM users';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        return array_map(
            static fn (array $row): User => User::fromRow($row),
            $this->fetchAll($sql, $params)
        );
    }

    public function count(?string $role = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM users';
        $params = [];
        if ($role !== null) {
            $sql .= ' WHERE role = :role';
            $params['role'] = $role;
        }
        $row = $this->fetchOne($sql, $params);

        return (int) ($row['total'] ?? 0);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        $this->insert('users', $data);

        return $this->findById((string) $data['id'])
            ?? throw new \RuntimeException('No se pudo recuperar el usuario creado.');
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): User
    {
        $allowed = [
            'email', 'password_hash', 'full_name', 'role', 'status', 'phone',
            'document_type', 'document_number', 'must_change_password', 'last_login_at', 'updated_at',
        ];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('users', $id, $data);
        }

        return $this->findById($id)
            ?? throw new \RuntimeException('Usuario no encontrado después de actualizar.');
    }

    public function touchLastLogin(string $id, string $at): void
    {
        $this->execute(
            'UPDATE users SET last_login_at = :at, updated_at = :at WHERE id = :id',
            ['id' => $id, 'at' => $at]
        );
    }

    public function updatePassword(string $id, string $hash, string $at): void
    {
        $this->execute(
            'UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = :at WHERE id = :id',
            ['id' => $id, 'hash' => $hash, 'at' => $at]
        );
    }

    public function emailExists(string $email, ?string $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM users WHERE LOWER(email) = LOWER(:email)';
        $params = ['email' => trim($email)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }
        $row = $this->fetchOne($sql, $params);

        return (int) ($row['total'] ?? 0) > 0;
    }

    /** @return array<string, int> */
    public function countByRole(): array
    {
        $rows = $this->fetchAll('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['role']] = (int) $row['total'];
        }

        return $result;
    }
}
