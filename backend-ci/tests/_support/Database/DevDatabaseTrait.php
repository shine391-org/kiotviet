<?php

namespace Tests\Support\Database;

use Config\Database;

/**
 * DevDatabaseTrait - dùng schema sẵn có ở group "tests", chỉ truncate data trước mỗi test.
 * Bắt buộc dùng với SchemaTrait cụ thể và tuân thủ TESTING-RULES (không DROP/ALTER).
 */
trait DevDatabaseTrait
{
    private static bool $schemaReady = true; // giả định schema đã có sẵn từ dump/migration

    protected function setUpDatabase(): void
    {
        // Ép defaultGroup sang 'tests' để dùng DB test.
        $config = config('Database');
        $config->defaultGroup = 'tests';
        \Config\Services::reset(true);

        $this->db = Database::connect('tests');
        $this->db->transBegin();
        $this->bootstrapTestData();
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->db) && $this->db->connID) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }
            $this->db->close();
        }
    }

    /**
     * Hook cho test muốn seed thêm dữ liệu sau khi transBegin.
     */
    protected function bootstrapTestData(): void
    {
        // override nếu cần
    }
}
