<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database as DatabaseConfig;

/**
 * Đảm bảo cấu hình DB đọc đúng biến môi trường cho dev/test.
 *
 * @agent-test: Database config env overrides
 * @agent-pattern: Config sanity test
 * @agent-reusable: MEDIUM
 */
final class DatabaseConfigTest extends CIUnitTestCase
{
    public function testDefaultDatabaseUsesEnvOverride(): void
    {
        $original = getenv('DB_NAME');
        putenv('DB_NAME=test_db_override');

        $config = new DatabaseConfig();

        $this->assertSame('test_db_override', $config->default['database']);

        // restore
        if ($original === false) {
            putenv('DB_NAME');
        } else {
            putenv('DB_NAME=' . $original);
        }
    }

    public function testTestsGroupUsesDbTestDefaults(): void
    {
        $originalHost = getenv('database.tests.hostname');
        $originalDb = getenv('database.tests.database');

        // Clear overrides to assert defaults
        putenv('database.tests.hostname');
        putenv('database.tests.database');

        $config = new DatabaseConfig();

        $this->assertSame('db-test', $config->tests['hostname']);
        $this->assertSame('lanocrm_test', $config->tests['database']);

        // restore
        if ($originalHost === false) {
            putenv('database.tests.hostname');
        } else {
            putenv('database.tests.hostname=' . $originalHost);
        }

        if ($originalDb === false) {
            putenv('database.tests.database');
        } else {
            putenv('database.tests.database=' . $originalDb);
        }
    }
}
