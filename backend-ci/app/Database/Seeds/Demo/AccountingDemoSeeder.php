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

        $requiredTables = ['chart_of_accounts', 'journal_entries', 'journal_entry_lines'];
        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                echo "      ⚠ Skipped Accounting demo (missing table: {$table})\n";
                return;
            }
        }
        if (! $this->db->fieldExists('account_type', 'chart_of_accounts')) {
            echo "      ⚠ Skipped Accounting demo (missing column: account_type in chart_of_accounts)\n";
            return;
        }
        
        $now = Time::now();

        // 1. Chart of Accounts (Simplified)
        $accounts = [
            ['code' => '111', 'name' => 'Tiền mặt', 'account_type' => 'asset'],
            ['code' => '112', 'name' => 'Tiền gửi ngân hàng', 'account_type' => 'asset'],
            ['code' => '131', 'name' => 'Phải thu khách hàng', 'account_type' => 'asset'],
            ['code' => '156', 'name' => 'Hàng hóa', 'account_type' => 'asset'],
            ['code' => '331', 'name' => 'Phải trả người bán', 'account_type' => 'liability'],
            ['code' => '511', 'name' => 'Doanh thu bán hàng', 'account_type' => 'revenue'],
            ['code' => '632', 'name' => 'Giá vốn hàng bán', 'account_type' => 'expense'],
            ['code' => '642', 'name' => 'Chi phí quản lý', 'account_type' => 'expense'],
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
