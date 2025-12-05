<?php

namespace Tests\Support\Database;

/**
 * @agent-test-helper: Reset schema for order templates/orders
 * @agent-pattern: Truncate-only schema reset
 * @agent-reusable: HIGH
 */
trait OrderTemplateSchemaTrait
{
    protected function resetOrderTemplateSchema(): void
    {
        $db = property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
        $tables = [
            'order_template_items',
            'order_templates',
            'order_subscriptions',
            'order_items',
            'orders',
            'price_list_items',
            'price_lists',
            'product_variants_v2',
            'products',
            'branches',
            'customers',
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
