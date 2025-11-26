<?php

namespace Tests\Support\Database;

/**
 * WebhookSchemaTrait - Webhook database schema (MySQL-only)
 * 
 * @agent-trait: Webhook tables testing schema
 * @agent-pattern: MySQL-only schema creation (SQLite removed)
 * @agent-reusable: HIGH
 */
trait WebhookSchemaTrait
{
    /**
     * Reset webhook schema for testing (MySQL-only)
     * 
     * @agent-pattern: Standard schema reset - COPY THIS
     * @agent-use: Call this in setUp() for webhook table tests
     */
    protected function resetWebhookSchema(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');

        foreach (['webhook_events', 'webhook_subscriptions'] as $table) {
            $this->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }

        // Create tables with MySQL-specific syntax
        $this->createSupportTables();
        $this->createWebhookSubscriptionTables();
        $this->createWebhookEventTables();
        
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
    
    /**
     * Create webhook subscription tables (MySQL-only)
     */
    private function createWebhookSubscriptionTables(): void
    {
        $this->db->query("CREATE TABLE webhook_subscriptions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(100) NOT NULL,
            target_url VARCHAR(255) NOT NULL,
            secret VARCHAR(255) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Create webhook event tables (MySQL-only)
     */
    private function createWebhookEventTables(): void
    {
        $this->db->query("CREATE TABLE webhook_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(100) NOT NULL,
            payload JSON,
            status VARCHAR(20) DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_error TEXT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Ensure common support tables exist for webhook-related tests.
     */
    private function createSupportTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS branches (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NULL,
            code VARCHAR(50) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NULL,
            email VARCHAR(100) NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
