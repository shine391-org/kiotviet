<?php

namespace Tests\Support\Database;

/**
 * Reset price list related tables for tests.
 *
 * @agent-test-support: Price lists schema reset
 * @agent-pattern: Truncate with FK disable
 * @agent-reusable: MEDIUM
 */
trait PriceListSchemaTrait
{
    protected function resetPriceListSchema(): void
    {
        $tables = [
            'price_list_items',
            'price_lists',
            'price_history',
            'project_price_lists',
            'customer_price_lists',
            'customer_groups',
            'product_variants_v2',
            'products',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        $existing = array_flip($this->db->listTables());
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
