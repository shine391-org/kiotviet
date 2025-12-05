<?php

namespace Tests\Support\Database;

trait InvoiceSchemaTrait
{
    protected function resetInvoiceSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = ['invoice_orders', 'invoices', 'orders', 'order_payments', 'customers', 'branches', 'users'];
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
