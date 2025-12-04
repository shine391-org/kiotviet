<?php

namespace Tests\Support\Database;

trait ProductSchemaTrait
{
    protected function resetProductSchema(): void
    {
        // Assumes $this->db is initialized and schema đã có sẵn.
        $tables = [
            'product_attribute_values',
            'product_attribute_options',
            'product_attributes',
            'product_images',
            'product_category_links',
            'product_variants_v2',
            'products',
            'branches',
            'users',
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
