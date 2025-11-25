<?php

namespace Tests\Support\Database;

use CodeIgniter\Database\Config;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database as DatabaseConfig;

/**
 * DevDatabaseTrait - MySQL-only testing foundation
 * 
 * Provides unified MySQL database connection and transaction management
 * for all test types (Unit, Integration, Feature).
 * 
 * @agent-trait: MySQL-only testing foundation
 * @agent-pattern: Transaction-based isolation with real MySQL
 * @agent-reusable: HIGH - Use in ALL test classes
 */
trait DevDatabaseTrait
{
    /**
     * Database connection instance
     */
    protected $db;

    /**
     * Transaction started flag
     */
    private bool $transactionStarted = false;

    /**
     * Setup MySQL database connection and start transaction
     * 
     * @agent-use: Call in setUp() method of ALL test classes
     * @agent-pattern: Standard MySQL test setup
     */
    protected function setUpDatabase(): void
    {
        // Ensure we're using the 'tests' database group
        $config = config(DatabaseConfig::class);
        $config->defaultGroup = 'tests';

        try {
            // Connect to MySQL test database
            $this->db = \Config\Database::connect('tests', false);
            
            if (!$this->db) {
                throw new DatabaseException('Failed to connect to test database');
            }

            // Start transaction for isolation and fast cleanup
            $this->db->transStart();
            $this->transactionStarted = true;

        } catch (\Exception $e) {
            throw new DatabaseException(
                'Database setup failed: ' . $e->getMessage() . 
                '. Ensure MySQL test container is running: docker-compose up -d db-test'
            );
        }
    }

    /**
     * Rollback transaction and close connection
     * 
     * @agent-use: Call in tearDown() method of ALL test classes
     * @agent-pattern: Standard MySQL test cleanup
     */
    protected function tearDownDatabase(): void
    {
        try {
            if ($this->transactionStarted && $this->db) {
                // Rollback transaction to clean test data
                $this->db->transRollback();
                $this->transactionStarted = false;
            }

            // Close connection
            if ($this->db) {
                $this->db->close();
                $this->db = null;
            }
        } catch (\Exception $e) {
            // Log error but don't throw to avoid masking test failures
            error_log('Database teardown error: ' . $e->getMessage());
        }
    }

    /**
     * Get database connection (for backward compatibility)
     * 
     * @return \CodeIgniter\Database\BaseConnection
     */
    protected function getDatabase(): \CodeIgniter\Database\BaseConnection
    {
        return $this->db;
    }

    /**
     * Check if database connection is active
     * 
     * @return bool
     */
    protected function isDatabaseConnected(): bool
    {
        return $this->db && !$this->db->connID === false;
    }

    /**
     * Execute raw SQL query (for complex setup)
     * 
     * @param string $sql
     * @return bool
     */
    protected function executeRaw(string $sql): bool
    {
        try {
            return $this->db->query($sql);
        } catch (\Exception $e) {
            throw new DatabaseException('Query failed: ' . $e->getMessage());
        }
    }

    /**
     * Truncate all tables with foreign key checks disabled
     * 
     * @agent-use: For complete cleanup between tests if needed
     * @agent-pattern: MySQL-specific truncate with FK handling
     */
    protected function truncateAllTables(): void
    {
        try {
            // Disable foreign key checks
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            
            // Get all table names
            $tables = $this->db->getTableList();
            
            // Truncate each table
            foreach ($tables as $table) {
                $this->db->table($table)->truncate();
            }
            
            // Re-enable foreign key checks
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            
        } catch (\Exception $e) {
            throw new DatabaseException('Truncate failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify MySQL connection and database existence
     * 
     * @return bool
     */
    protected function verifyMySqlConnection(): bool
    {
        try {
            if (!$this->db) {
                return false;
            }
            
            // Test simple query
            $result = $this->db->query('SELECT 1 as test')->getRow();
            return isset($result->test) && $result->test === 1;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}