<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * HRDemoSeeder - Demo HR data
 * 
 * @agent-seeder: Demo HR
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 */
class HRDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Demo HR data (Departments, Employees, Attendance)...\n";

        // Skip if HR tables are not present in schema
        $requiredTables = ['departments', 'positions', 'employees', 'attendance', 'leave_applications'];
        foreach ($requiredTables as $table) {
            if (! $this->db->tableExists($table)) {
                echo "      ⚠ Skipped HR demo (missing table: {$table})\n";
                return;
            }
        }
        
        $now = Time::now();

        // 1. Departments
        $departments = [
            ['id' => 1, 'name' => 'Ban Giám Đốc', 'code' => 'BGD', 'description' => 'Board of Directors', 'status' => 'active'],
            ['id' => 2, 'name' => 'Phòng Kinh Doanh', 'code' => 'KD', 'description' => 'Sales Department', 'status' => 'active'],
            ['id' => 3, 'name' => 'Phòng Kế Toán', 'code' => 'KT', 'description' => 'Accounting Department', 'status' => 'active'],
            ['id' => 4, 'name' => 'Phòng Nhân Sự', 'code' => 'NS', 'description' => 'HR Department', 'status' => 'active'],
            ['id' => 5, 'name' => 'Kho Vận', 'code' => 'KV', 'description' => 'Logistics Department', 'status' => 'active'],
        ];

        foreach ($departments as $dept) {
            $dept['created_at'] = $now;
            $dept['updated_at'] = $now;
            $this->db->table('departments')->ignore(true)->insert($dept);
        }

        // 2. Positions
        $positions = [
            ['id' => 1, 'title' => 'Giám Đốc', 'code' => 'CEO', 'department_id' => 1, 'level' => 1],
            ['id' => 2, 'title' => 'Trưởng Phòng', 'code' => 'MGR', 'department_id' => 2, 'level' => 2],
            ['id' => 3, 'title' => 'Nhân Viên', 'code' => 'STAFF', 'department_id' => 2, 'level' => 3],
            ['id' => 4, 'title' => 'Kế Toán Trưởng', 'code' => 'ACC_MGR', 'department_id' => 3, 'level' => 2],
            ['id' => 5, 'title' => 'Thủ Kho', 'code' => 'WH_KEEPER', 'department_id' => 5, 'level' => 3],
        ];

        foreach ($positions as $pos) {
            $pos['created_at'] = $now;
            $pos['updated_at'] = $now;
            $this->db->table('positions')->ignore(true)->insert($pos);
        }

        // 3. Employees
        $employees = [
            [
                'id' => 1,
                'user_id' => 1, // Linked to admin user
                'code' => 'EMP001',
                'employee_code' => 'EMP001',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'full_name' => 'Admin User',
                'email' => 'admin@lanocrm.local',
                'phone' => '0900000001',
                'department_id' => 1,
                'position_id' => 1,
                'hire_date' => $now->subYears(2)->toDateString(),
                'status' => 'active',
            ],
            [
                'id' => 2,
                'user_id' => 2, // Linked to manager user
                'code' => 'EMP002',
                'employee_code' => 'EMP002',
                'first_name' => 'Manager',
                'last_name' => 'Test',
                'full_name' => 'Manager Test',
                'email' => 'manager@lanocrm.local',
                'phone' => '0900000002',
                'department_id' => 2,
                'position_id' => 2,
                'hire_date' => $now->subYears(1)->toDateString(),
                'status' => 'active',
            ],
            [
                'id' => 3,
                'user_id' => 3, // Linked to staff user
                'code' => 'EMP003',
                'employee_code' => 'EMP003',
                'first_name' => 'Staff',
                'last_name' => 'Test',
                'full_name' => 'Staff Test',
                'email' => 'staff@lanocrm.local',
                'phone' => '0900000003',
                'department_id' => 2,
                'position_id' => 3,
                'hire_date' => $now->subMonths(6)->toDateString(),
                'status' => 'active',
            ],
        ];

        foreach ($employees as $emp) {
            $emp['created_at'] = $now;
            $emp['updated_at'] = $now;
            $this->db->table('employees')->ignore(true)->insert($emp);
        }

        // 4. Attendance (Sample data for last 5 days)
        for ($i = 0; $i < 5; $i++) {
            $date = $now->subDays($i)->toDateString();
            foreach ($employees as $emp) {
                $this->db->table('attendance')->ignore(true)->insert([
                    'employee_id' => $emp['id'],
                    'date' => $date,
                    'check_in' => '08:00:00',
                    'check_out' => '17:00:00',
                    'status' => 'present',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 5. Leave Types
        $leaveTypes = [
            ['id' => 1, 'leave_name' => 'Annual Leave', 'default_allocation' => 12],
            ['id' => 2, 'leave_name' => 'Sick Leave', 'default_allocation' => 10],
            ['id' => 3, 'leave_name' => 'Unpaid Leave', 'default_allocation' => 0],
        ];
        
        foreach ($leaveTypes as $type) {
            $type['created_at'] = $now;
            $type['updated_at'] = $now;
            $this->db->table('leave_types')->ignore(true)->insert($type);
        }

        // 6. Leave Applications
        $leaves = [
            [
                'employee_id' => 3,
                'leave_type_id' => 1, // Annual Leave
                'from_date' => $now->addDays(5)->toDateString(),
                'to_date' => $now->addDays(6)->toDateString(),
                'reason' => 'Personal matters',
                'status' => 'pending',
            ]
        ];

        foreach ($leaves as $leave) {
            $leave['created_at'] = $now;
            $leave['updated_at'] = $now;
            $this->db->table('leave_applications')->ignore(true)->insert($leave);
        }

        echo "      ✓ Created HR data\n";
    }
}
