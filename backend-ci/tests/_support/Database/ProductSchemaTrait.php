<?php

namespace Tests\Support\Database;

trait ProductSchemaTrait
{
    protected function resetSchema(): void
    {
        // Assumes $this->db is initialized and schema already created by DevDatabaseTrait.
        $tables = [
            'product_attribute_values',
            'product_images',
            'product_category_links',
            'product_variants_v2',
            'products',
            'attributes',
            'attribute_options',
            'branches',
            'users',
        ];

        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (in_array($table, $this->db->listTables(), true)) {
                $this->db->table($table)->truncate();
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
