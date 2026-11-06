<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

use PDO;

abstract class PdoRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /** @param array<int|string, mixed> $params */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    /** @param array<string, mixed> $data */
    protected function insert(string $table, array $data): void
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $this->execute($sql, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function updateById(string $table, string $id, array $data): int
    {
        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }
        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $table, implode(', ', $assignments));
        $data['id'] = $id;

        return $this->execute($sql, $data);
    }
}
