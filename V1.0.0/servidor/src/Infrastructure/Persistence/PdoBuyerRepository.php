<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use Skatenea\Domain\Buyer\Buyer;
use Skatenea\Domain\Buyer\BuyerRepositoryInterface;

final class PdoBuyerRepository extends PdoRepository implements BuyerRepositoryInterface
{
    public function findById(string $id): ?Buyer
    {
        $row = $this->fetchOne('SELECT * FROM buyers WHERE id = :id LIMIT 1', ['id' => $id]);

        return $row === null ? null : Buyer::fromRow($row);
    }

    public function findByEmail(string $email): ?Buyer
    {
        $row = $this->fetchOne(
            'SELECT * FROM buyers WHERE LOWER(email) = LOWER(:email) LIMIT 1',
            ['email' => trim($email)]
        );

        return $row === null ? null : Buyer::fromRow($row);
    }

    public function findByUserId(string $userId): ?Buyer
    {
        $row = $this->fetchOne('SELECT * FROM buyers WHERE user_id = :user_id LIMIT 1', ['user_id' => $userId]);

        return $row === null ? null : Buyer::fromRow($row);
    }

    public function findByPhone(string $phone): ?Buyer
    {
        $row = $this->fetchOne('SELECT * FROM buyers WHERE phone = :phone LIMIT 1', ['phone' => $phone]);

        return $row === null ? null : Buyer::fromRow($row);
    }

    public function findByDocument(string $type, string $number): ?Buyer
    {
        $row = $this->fetchOne(
            'SELECT * FROM buyers WHERE document_type = :type AND document_number = :number LIMIT 1',
            ['type' => $type, 'number' => $number]
        );

        return $row === null ? null : Buyer::fromRow($row);
    }

    /** @return list<Buyer> */
    public function search(string $term, ?string $createdBy = null, int $limit = 20): array
    {
        $like = '%' . mb_strtolower(trim($term)) . '%';
        $sql = 'SELECT DISTINCT b.* FROM buyers b
             WHERE (LOWER(b.full_name) LIKE :term
                OR LOWER(COALESCE(b.email, \'\')) LIKE :term
                OR b.phone LIKE :term
                OR LOWER(COALESCE(b.document_number, \'\')) LIKE :term)';
        $params = ['term' => $like];

        if ($createdBy !== null) {
            $sql .= ' AND (b.created_by = :created_by OR EXISTS (
                SELECT 1 FROM tickets t WHERE t.buyer_id = b.id AND t.seller_id = :created_by
            ))';
            $params['created_by'] = $createdBy;
        }

        $sql .= ' ORDER BY b.full_name ASC LIMIT ' . max(1, $limit);
        $rows = $this->fetchAll($sql, $params);

        return array_map(static fn (array $row): Buyer => Buyer::fromRow($row), $rows);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Buyer
    {
        $this->insert('buyers', $data);

        return $this->findById((string) $data['id'])
            ?? throw new \RuntimeException('No se pudo recuperar el comprador creado.');
    }

    /** @param array<string, mixed> $data */
    public function update(string $id, array $data): Buyer
    {
        $allowed = ['full_name', 'email', 'phone', 'document_type', 'document_number', 'user_id', 'created_by', 'updated_at'];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data !== []) {
            $this->updateById('buyers', $id, $data);
        }

        return $this->findById($id)
            ?? throw new \RuntimeException('Comprador no encontrado después de actualizar.');
    }
}
