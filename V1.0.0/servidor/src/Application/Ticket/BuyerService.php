<?php

declare(strict_types=1);

namespace Skatenea\Application\Ticket;

use Skatenea\Application\AuditLogger;
use Skatenea\Core\HttpException;
use Skatenea\Core\Request;
use Skatenea\Core\Uuid;
use Skatenea\Domain\Buyer\Buyer;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;
use Skatenea\Domain\User\User;

final class BuyerService
{
    public function __construct(
        private readonly BuyerRepositoryInterface $buyers,
        private readonly AuditLogger $audit
    ) {
    }

    /** @return list<Buyer> */
    public function search(User $actor, string $term): array
    {
        if (strlen(trim($term)) < 2) {
            return [];
        }
        $createdBy = $actor->role === User::ROLE_SELLER ? $actor->id : null;

        return $this->buyers->search($term, $createdBy);
    }

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data, Request $request): Buyer
    {
        if (!in_array($actor->role, [User::ROLE_SUPER_ADMIN, User::ROLE_LEADER, User::ROLE_SELLER], true)) {
            throw HttpException::forbidden('No puedes registrar compradores.');
        }
        $buyer = $this->createInternal($actor, $data);
        $this->audit->record($actor, 'buyer.created', 'buyer', $buyer->id, [], $buyer->toArray(), $request);

        return $buyer;
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, string $id, array $data, Request $request): Buyer
    {
        $buyer = $this->findOrFail($id);
        if ($actor->role === User::ROLE_SELLER && $buyer->createdBy !== $actor->id) {
            throw HttpException::forbidden('Solo puedes editar compradores que registraste.');
        }
        if (!in_array($actor->role, [User::ROLE_SUPER_ADMIN, User::ROLE_LEADER, User::ROLE_SELLER], true)) {
            throw HttpException::forbidden('No puedes editar compradores.');
        }

        $payload = array_intersect_key($data, array_flip([
            'full_name', 'email', 'phone', 'document_type', 'document_number',
        ]));
        $payload['updated_at'] = gmdate('Y-m-d H:i:s');
        $updated = $this->buyers->update($id, $payload);
        $this->audit->record($actor, 'buyer.updated', 'buyer', $id, $buyer->toArray(), $updated->toArray(), $request);

        return $updated;
    }

    /** @param array<string, mixed> $data */
    public function ensureBuyer(User $actor, array $data): Buyer
    {
        if (!empty($data['buyer_id'])) {
            return $this->findOrFail((string) $data['buyer_id']);
        }

        $documentType = isset($data['document_type']) ? (string) $data['document_type'] : null;
        $documentNumber = isset($data['document_number']) ? trim((string) $data['document_number']) : null;
        if ($documentType !== null && $documentNumber !== null && $documentNumber !== '') {
            $existing = $this->buyers->findByDocument($documentType, $documentNumber);
            if ($existing !== null) {
                return $existing;
            }
        }

        $email = isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null;
        if ($email !== null && $email !== '') {
            $existing = $this->buyers->findByEmail($email);
            if ($existing !== null) {
                return $existing;
            }
        }

        $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';
        if ($phone !== '') {
            $existing = $this->buyers->findByPhone($phone);
            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->createInternal($actor, $data);
    }

    public function findOrFail(string $id): Buyer
    {
        $buyer = $this->buyers->findById($id);
        if ($buyer === null) {
            throw HttpException::notFound('Comprador no encontrado.');
        }

        return $buyer;
    }

    /** @param array<string, mixed> $data */
    private function createInternal(User $actor, array $data): Buyer
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($fullName === '' || $phone === '') {
            throw HttpException::unprocessable('Nombre y teléfono del comprador son obligatorios.');
        }
        $email = isset($data['email']) && $data['email'] !== '' ? mb_strtolower(trim((string) $data['email'])) : null;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw HttpException::unprocessable('El correo del comprador no es válido.');
        }

        $now = gmdate('Y-m-d H:i:s');

        return $this->buyers->create([
            'id' => Uuid::v4(),
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'user_id' => null,
            'created_by' => $actor->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
