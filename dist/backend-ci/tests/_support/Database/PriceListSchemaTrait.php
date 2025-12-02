<?php

namespace Tests\Support\Database;

/**
 * PriceListSchemaTrait - truncate-only (schema đến từ migrations).
 */
trait PriceListSchemaTrait
{
    protected function resetPriceListSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = [
            'order_items', 'orders', 'order_sequences',
            'price_list_items', 'price_lists',
            'product_images', 'product_attribute_values',
            'product_attribute_options', 'product_attributes',
            'product_variants_v2', 'products',
            'product_category_links', 'product_categories',
            'inventory_stock'
        ];
        $this->truncateTables($db, $tables);
    }

    private function getSchemaDb()
    {
        return property_exists($this, 'db') && $this->db ? $this->db : \Config\Database::connect('tests');
    }

    private function truncateTables($db, array $tables): void
    {
        $existing = array_flip($db->listTables());
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $t) {
            if (isset($existing[$t])) {
                $db->table($t)->truncate();
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
