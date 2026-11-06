<?php

declare(strict_types=1);

namespace Skatenea\Auth;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\User\User;

final class ApiKeyService
{
    public function __construct(
        private readonly ApiKeyRepositoryInterface $repository,
        private readonly AuditLogger $audit
    ) {
    }

    /**
     * @param list<string> $scopes
     * @return array<string, mixed>
     */
    public function create(User $actor, string $name, array $scopes, ?string $expiresAt = null): array
    {
        $scopes = array_values(array_unique($scopes));
        if ($scopes === []) {
            throw HttpException::unprocessable('Debes indicar al menos un scope.');
        }
        $invalid = array_diff($scopes, Rbac::ALL);
        if ($invalid !== []) {
            throw HttpException::unprocessable('Scopes inválidos.', ['invalid' => array_values($invalid)]);
        }
        if ($expiresAt !== null && strtotime($expiresAt) === false) {
            throw HttpException::unprocessable('La fecha de expiración no es válida.');
        }

        $prefix = 'sk_' . bin2hex(random_bytes(5));
        $raw = $prefix . '.' . bin2hex(random_bytes(32));
        $id = Uuid::v4();
        $now = gmdate('Y-m-d H:i:s');

        $this->repository->create([
            'id' => $id,
            'name' => $name,
            'prefix' => $prefix,
            'key_hash' => hash('sha256', $raw),
            'scopes' => json_encode($scopes, JSON_THROW_ON_ERROR),
            'created_by' => $actor->id,
            'last_used_at' => null,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'created_at' => $now,
        ]);

        $this->audit->record($actor, 'api_key.created', 'api_key', $id, [], [
            'name' => $name,
            'prefix' => $prefix,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        return [
            'id' => $id,
            'name' => $name,
            'prefix' => $prefix,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
            'key' => $raw,
        ];
    }

    /** @return array<string, mixed>|null */
    public function verify(string $rawKey, Request $request): ?array
    {
        if (!str_contains($rawKey, '.')) {
            return null;
        }
        [$prefix] = explode('.', $rawKey, 2);
        $record = $this->repository->findByPrefix($prefix);
        if ($record === null) {
            return null;
        }
        if (!hash_equals((string) $record['key_hash'], hash('sha256', $rawKey))) {
            return null;
        }
        if (!empty($record['revoked_at'])) {
            return null;
        }
        if (!empty($record['expires_at']) && strtotime((string) $record['expires_at']) < time()) {
            return null;
        }

        $this->repository->touch((string) $record['id'], gmdate('Y-m-d H:i:s'));
        $record['scopes'] = json_decode((string) $record['scopes'], true) ?: [];

        return $record;
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map(
            static function (array $row): array {
                $row['scopes'] = json_decode((string) $row['scopes'], true) ?: [];
                return $row;
            },
            $this->repository->all()
        );
    }

    public function revoke(User $actor, string $id): void
    {
        $this->repository->revoke($id, gmdate('Y-m-d H:i:s'));
        $this->audit->record($actor, 'api_key.revoked', 'api_key', $id);
    }
}
