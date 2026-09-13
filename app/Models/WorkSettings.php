<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class WorkSettings
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require BASE_PATH . '/config/database.php';
    }

    public function all(): array
    {
        $rows = $this->db->query('SELECT setting_key, setting_value FROM work_settings')->fetchAll();
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function update(array $values): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO work_settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($values as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => (string)$value]);
        }
    }
}
