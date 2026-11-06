<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

final class PdoPreferencesRepository extends PdoRepository implements PreferencesRepositoryInterface
{
    /** @return array<string, mixed> */
    public function get(string $userId): array
    {
        $row = $this->fetchOne('SELECT * FROM user_preferences WHERE user_id = :user_id LIMIT 1', [
            'user_id' => $userId,
        ]);

        return $row ?? [
            'user_id' => $userId,
            'theme' => 'aurora',
            'mode' => 'dark',
            'geometry' => 'soft',
            'background' => 'aurora',
            'sidebar_collapsed' => 0,
        ];
    }

    /** @param array<string, mixed> $data */
    public function save(string $userId, array $data): void
    {
        $allowed = ['theme', 'mode', 'geometry', 'background', 'sidebar_collapsed'];
        $data = array_intersect_key($data, array_flip($allowed));
        if ($data === []) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->fetchOne(
            'SELECT user_id FROM user_preferences WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        if ($existing === null) {
            $this->insert('user_preferences', array_merge($data, [
                'user_id' => $userId,
                'updated_at' => $now,
            ]));
            return;
        }

        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }
        $data['user_id'] = $userId;
        $data['updated_at'] = $now;
        $this->execute(
            'UPDATE user_preferences SET ' . implode(', ', $assignments) . ', updated_at = :updated_at
             WHERE user_id = :user_id',
            $data
        );
    }
}
