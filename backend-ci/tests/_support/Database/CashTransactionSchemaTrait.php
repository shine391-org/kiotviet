<?php

namespace Tests\Support\Database;

trait CashTransactionSchemaTrait
{
    protected function resetCashTransactionSchema(): void
    {
        $db = $this->getSchemaDb();
        $tables = ['cash_transactions', 'orders', 'purchase_orders', 'branches', 'users'];
        $this->truncateTables($db, $tables);
    }

    /**
     * Schema creation is handled by golden migration; keep stub for backward compat.
     */
    protected function createSupportingTables(): void
    {
        // no-op
    }

    protected function seedCashTransaction(array $data): int
    {
        $db = $this->getSchemaDb();
        $payload = array_merge([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        $db->table('cash_transactions')->insert($payload);
        return (int) $db->insertID();
    }

    protected function seedCashTransactions(array $transactions): array
    {
        $ids = [];
        foreach ($transactions as $data) {
            $ids[] = $this->seedCashTransaction($data);
        }
        return $ids;
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
