<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * CRMDemoSeeder - Demo CRM data
 * 
 * @agent-seeder: Demo CRM
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class CRMDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo CRM data (Leads, Opportunities, Tasks)...\n";

        // Skip if CRM schema không đủ cột cần thiết
        $requiredTables = ['leads', 'opportunities', 'tasks'];
        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                echo "      ⚠ Skipped CRM demo (missing table: {$table})\n";
                return;
            }
        }
        $leadColumns = ['first_name', 'last_name', 'assigned_to'];
        foreach ($leadColumns as $col) {
            if (! $this->db->fieldExists($col, 'leads')) {
                echo "      ⚠ Skipped CRM demo (missing column in leads: {$col})\n";
                return;
            }
        }
        
        $now = Time::now();

        // 1. Leads
        $leads = [
            [
                'id' => 1,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '0912345678',
                'company' => 'Example Corp',
                'status' => 'new',
                'source' => 'website',
                'assigned_to' => 2, // Manager
            ],
            [
                'id' => 2,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '0987654321',
                'company' => 'Tech Solutions',
                'status' => 'contacted',
                'source' => 'referral',
                'assigned_to' => 3, // Staff
            ],
        ];

        foreach ($leads as $lead) {
            $lead['created_at'] = $now;
            $lead['updated_at'] = $now;
            $this->db->table('leads')->ignore(true)->insert($lead);
        }

        // 2. Opportunities
        $opportunities = [
            [
                'id' => 1,
                'name' => 'Big Deal with Example Corp',
                'customer_id' => 1, // Assuming customer 1 exists
                'amount' => 50000000,
                'stage' => 'proposal',
                'probability' => 60,
                'close_date' => $now->addDays(30)->toDateString(),
                'assigned_to' => 2,
            ],
            [
                'id' => 2,
                'name' => 'Service Contract',
                'customer_id' => 2,
                'amount' => 12000000,
                'stage' => 'negotiation',
                'probability' => 80,
                'close_date' => $now->addDays(15)->toDateString(),
                'assigned_to' => 3,
            ],
        ];

        foreach ($opportunities as $opp) {
            $opp['created_at'] = $now;
            $opp['updated_at'] = $now;
            $this->db->table('opportunities')->ignore(true)->insert($opp);
        }

        // 3. Tasks
        $tasks = [
            [
                'subject' => 'Call John Doe',
                'description' => 'Follow up on proposal',
                'due_date' => $now->addDays(1)->toDateTimeString(),
                'status' => 'pending',
                'priority' => 'high',
                'related_to_type' => 'lead',
                'related_to_id' => 1,
                'assigned_to' => 2,
            ],
            [
                'subject' => 'Prepare Contract',
                'description' => 'Draft service agreement for Tech Solutions',
                'due_date' => $now->addDays(2)->toDateTimeString(),
                'status' => 'in_progress',
                'priority' => 'medium',
                'related_to_type' => 'opportunity',
                'related_to_id' => 2,
                'assigned_to' => 3,
            ],
        ];

        foreach ($tasks as $task) {
            $task['created_at'] = $now;
            $task['updated_at'] = $now;
            $this->db->table('tasks')->ignore(true)->insert($task);
        }

        echo "      ✓ Created CRM data\n";
    }
}
