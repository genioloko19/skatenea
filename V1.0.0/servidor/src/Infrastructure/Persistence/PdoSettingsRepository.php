<?php

declare(strict_types=1);

namespace Skatenea\Infrastructure\Persistence;

final class PdoSettingsRepository extends PdoRepository implements SettingsRepositoryInterface
{
    /** @return array<string, mixed> */
    public function all(): array
    {
        $rows = $this->fetchAll('SELECT setting_key, setting_value FROM settings ORDER BY setting_key');
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = json_decode((string) $row['setting_value'], true);
        }

        return $result;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->fetchOne(
            'SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1',
            ['key' => $key]
        );
        if ($row === null) {
            return $default;
        }

        return json_decode((string) $row['setting_value'], true);
    }

    public function set(string $key, mixed $value, ?string $updatedBy = null): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $existing = $this->fetchOne(
            'SELECT setting_key FROM settings WHERE setting_key = :key LIMIT 1',
            ['key' => $key]
        );

        if ($existing === null) {
            $this->insert('settings', [
                'setting_key' => $key,
                'setting_value' => json_encode($value, JSON_THROW_ON_ERROR),
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]);
            return;
        }

        $this->execute(
            'UPDATE settings SET setting_value = :value, updated_by = :updated_by, updated_at = :updated_at
             WHERE setting_key = :key',
            [
                'key' => $key,
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
                'updated_by' => $updatedBy,
                'updated_at' => $now,
            ]
        );
    }
}
