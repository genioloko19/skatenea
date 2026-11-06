<?php

declare(strict_types=1);

namespace Skatenea\Domain\User;

final class User
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_LEADER = 'leader';
    public const ROLE_SELLER = 'seller';
    public const ROLE_BUYER = 'buyer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_LOCKED = 'locked';

    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $fullName,
        public readonly string $role,
        public readonly string $status,
        public readonly ?string $phone,
        public readonly ?string $documentType,
        public readonly ?string $documentNumber,
        public readonly bool $mustChangePassword,
        public readonly ?string $lastLoginAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly int $failedLoginAttempts = 0,
        public readonly ?string $lockedUntil = null
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['full_name'],
            (string) $row['role'],
            (string) $row['status'],
            isset($row['phone']) ? (string) $row['phone'] : null,
            isset($row['document_type']) ? (string) $row['document_type'] : null,
            isset($row['document_number']) ? (string) $row['document_number'] : null,
            (bool) ($row['must_change_password'] ?? false),
            isset($row['last_login_at']) ? (string) $row['last_login_at'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at'],
            (int) ($row['failed_login_attempts'] ?? 0),
            isset($row['locked_until']) ? (string) $row['locked_until'] : null
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && strtotime($this->lockedUntil) > time();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /** @return array<string, mixed> */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'role' => $this->role,
            'status' => $this->status,
            'phone' => $this->phone,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'must_change_password' => $this->mustChangePassword,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'locked_until' => $this->lockedUntil,
        ];
    }
}
