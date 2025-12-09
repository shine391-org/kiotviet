<?php

namespace Tests\Support\Database;

/**
 * Reset Loyalty-related tables for tests.
 *
 * @agent-test-support: Loyalty schema reset
 * @agent-pattern: Truncate with FK disable
 */
trait LoyaltySchemaTrait
{
    protected function resetLoyaltySchema(): void
    {
        $tables = [
            'loyalty_transactions',
            'loyalty_wallets',
            'loyalty_programs',
            'customer_groups',
            'customers',
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

    protected function createCustomerForLoyalty(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'Test Customer ' . uniqid(),
            'code' => 'CUST-' . uniqid(),
            'phone' => '0' . rand(100000000, 999999999),
            'customer_type' => 'INDIVIDUAL',
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('customers')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createLoyaltyProgram(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'Test Program ' . uniqid(),
            'status' => 'active',
            'earn_rate' => 0.01, // 1 point per 100 VND
            'redeem_rate' => 100, // 1 point = 100 VND
            'expiry_days' => 365,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('loyalty_programs')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    protected function createLoyaltyWallet(int $customerId, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'customer_id' => $customerId,
            'points_balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('loyalty_wallets')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }
}
