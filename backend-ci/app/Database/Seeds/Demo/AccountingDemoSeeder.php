<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * AccountingDemoSeeder - Demo Accounting data
 * 
 * @agent-seeder: Demo Accounting
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class AccountingDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo Accounting data (COA, Journal Entries)...\n";
        
        $now = Time::now();

        // 1. Chart of Accounts (Simplified)
        $accounts = [
            ['code' => '111', 'name' => 'Tiền mặt', 'type' => 'asset', 'is_active' => 1],
            ['code' => '112', 'name' => 'Tiền gửi ngân hàng', 'type' => 'asset', 'is_active' => 1],
            ['code' => '131', 'name' => 'Phải thu khách hàng', 'type' => 'asset', 'is_active' => 1],
            ['code' => '156', 'name' => 'Hàng hóa', 'type' => 'asset', 'is_active' => 1],
            ['code' => '331', 'name' => 'Phải trả người bán', 'type' => 'liability', 'is_active' => 1],
            ['code' => '511', 'name' => 'Doanh thu bán hàng', 'type' => 'revenue', 'is_active' => 1],
            ['code' => '632', 'name' => 'Giá vốn hàng bán', 'type' => 'expense', 'is_active' => 1],
            ['code' => '642', 'name' => 'Chi phí quản lý', 'type' => 'expense', 'is_active' => 1],
        ];

        foreach ($accounts as $acc) {
            $acc['created_at'] = $now;
            $acc['updated_at'] = $now;
            $this->db->table('chart_of_accounts')->ignore(true)->insert($acc);
        }

        // 2. Journal Entries
        $entries = [
            [
                'id' => 1,
                'reference_number' => 'JE-2024-001',
                'date' => $now->subDays(5)->toDateString(),
                'description' => 'Thu tiền bán hàng',
                'status' => 'posted',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'reference_number' => 'JE-2024-002',
                'date' => $now->subDays(2)->toDateString(),
                'description' => 'Thanh toán tiền điện',
                'status' => 'posted',
                'created_by' => 1,
            ],
        ];

        foreach ($entries as $entry) {
            $entry['created_at'] = $now;
            $entry['updated_at'] = $now;
            $this->db->table('journal_entries')->ignore(true)->insert($entry);
        }

        // 3. Journal Entry Lines
        $lines = [
            // Entry 1: Cash Sales
            ['journal_entry_id' => 1, 'account_code' => '111', 'debit' => 5000000, 'credit' => 0, 'description' => 'Thu tiền mặt'],
            ['journal_entry_id' => 1, 'account_code' => '511', 'debit' => 0, 'credit' => 5000000, 'description' => 'Doanh thu'],
            
            // Entry 2: Expense Payment
            ['journal_entry_id' => 2, 'account_code' => '642', 'debit' => 1000000, 'credit' => 0, 'description' => 'Chi phí điện'],
            ['journal_entry_id' => 2, 'account_code' => '112', 'debit' => 0, 'credit' => 1000000, 'description' => 'Chuyển khoản'],
        ];

        foreach ($lines as $line) {
            $line['created_at'] = $now;
            $line['updated_at'] = $now;
            $this->db->table('journal_entry_lines')->ignore(true)->insert($line);
        }

        echo "      ✓ Created Accounting data\n";
    }
}