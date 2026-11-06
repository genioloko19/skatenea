<?php

declare(strict_types=1);

namespace Skatenea\Domain\Buyer;

final class Buyer
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly ?string $email,
        public readonly string $phone,
        public readonly ?string $documentType,
        public readonly ?string $documentNumber,
        public readonly ?string $userId,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (string) $row['id'],
            (string) $row['full_name'],
            isset($row['email']) ? (string) $row['email'] : null,
            (string) $row['phone'],
            isset($row['document_type']) ? (string) $row['document_type'] : null,
            isset($row['document_number']) ? (string) $row['document_number'] : null,
            isset($row['user_id']) ? (string) $row['user_id'] : null,
            isset($row['created_by']) ? (string) $row['created_by'] : null,
            (string) $row['created_at'],
            (string) $row['updated_at']
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'user_id' => $this->userId,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
