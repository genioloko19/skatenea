<?php

declare(strict_types=1);

namespace Skatenea\Domain\Buyer;

interface BuyerRepositoryInterface
{
    public function findById(string $id): ?Buyer;

    public function findByEmail(string $email): ?Buyer;

    public function findByUserId(string $userId): ?Buyer;

    public function findByPhone(string $phone): ?Buyer;

    public function findByDocument(string $type, string $number): ?Buyer;

    /** @return list<Buyer> */
    public function search(string $term, ?string $createdBy = null, int $limit = 20): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): Buyer;

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Buyer;
}
