<?php

namespace Tests\Support\Database;

trait WebhookSchemaTrait
{
    protected function resetWebhookSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $isSqlite = strtolower($this->db->DBDriver ?? '') === 'sqlite3';
        $jsonType = $isSqlite ? 'TEXT' : 'JSON';
        $boolType = $isSqlite ? 'INTEGER' : 'TINYINT(1)';
        $idType = $isSqlite ? 'INTEGER' : 'BIGINT UNSIGNED';

        if (! $isSqlite) {
            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach (['webhook_events', 'db_webhook_events', 'webhook_subscriptions', 'db_webhook_subscriptions'] as $table) {
            $this->db->query("DROP TABLE IF EXISTS {$table}");
        }

        $this->db->query("CREATE TABLE webhook_subscriptions (
            id {$idType} PRIMARY KEY {$auto},
            event VARCHAR(100) NOT NULL,
            target_url VARCHAR(255) NOT NULL,
            secret VARCHAR(255) NULL,
            is_active {$boolType} DEFAULT 1,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE db_webhook_subscriptions (
            id {$idType} PRIMARY KEY {$auto},
            event VARCHAR(100) NOT NULL,
            target_url VARCHAR(255) NOT NULL,
            secret VARCHAR(255) NULL,
            is_active {$boolType} DEFAULT 1,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE webhook_events (
            id {$idType} PRIMARY KEY {$auto},
            event VARCHAR(100) NOT NULL,
            payload {$jsonType},
            status VARCHAR(20) DEFAULT 'pending',
            attempts INTEGER DEFAULT 0,
            last_error TEXT NULL,
            created_at TEXT,
            updated_at TEXT
        )");

        $this->db->query("CREATE TABLE db_webhook_events (
            id {$idType} PRIMARY KEY {$auto},
            event VARCHAR(100) NOT NULL,
            payload {$jsonType},
            status VARCHAR(20) DEFAULT 'pending',
            attempts INTEGER DEFAULT 0,
            last_error TEXT NULL,
            created_at TEXT,
            updated_at TEXT
        )");
    }
}
