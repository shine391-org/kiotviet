<?php

namespace Tests\Support\Database;

/**
 * @agent-test-helper: Reset schema for subscriptions
 * @agent-pattern: Truncate-only schema reset
 * @agent-reusable: HIGH
 */
trait SubscriptionSchemaTrait
{
    protected function resetSubscriptionSchema(): void
    {
        $db = property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
        $tables = [
            'subscription_cycles',
            'subscriptions',
            'order_items',
            'orders',
            'products',
            'branches',
        ];

        $existing = array_flip($db->listTables());
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $db->table($table)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
