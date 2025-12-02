<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seed master data mới: customer_groups, suppliers, organizations, devices.
 *
 * @agent-seeder: Master data
 * @agent-pattern: Idempotent inserts
 * @agent-reusable: MEDIUM
 */
class MasterDataSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Customer groups
        $customerGroups = [
            ['id' => 1, 'code' => 'CG-STD', 'name_vi' => 'Khách chuẩn', 'is_default' => 1],
            ['id' => 2, 'code' => 'CG-VIP', 'name_vi' => 'Khách VIP', 'is_default' => 0],
            ['id' => 3, 'code' => 'CG-WHS', 'name_vi' => 'Khách sỉ', 'is_default' => 0],
        ];
        foreach ($customerGroups as $group) {
            $group['status'] = 'active';
            $group['created_at'] = $now;
            $group['updated_at'] = $now;
            $this->db->table('customer_groups')->ignore(true)->insert($group);
        }

        // Suppliers
        $suppliers = [
            ['id' => 1, 'code' => 'SUP-001', 'name_vi' => 'Nhà cung cấp A', 'tax_code' => '0101010101'],
            ['id' => 2, 'code' => 'SUP-002', 'name_vi' => 'Nhà cung cấp B', 'tax_code' => '0202020202'],
        ];
        foreach ($suppliers as $supplier) {
            $supplier['status'] = 'active';
            $supplier['created_at'] = $now;
            $supplier['updated_at'] = $now;
            $this->db->table('suppliers')->ignore(true)->insert($supplier);
        }

        // Organizations
        $organizations = [
            ['id' => 1, 'code' => 'ORG-001', 'name_vi' => 'Tổ chức Demo 1', 'tax_code' => '0303030303'],
        ];
        foreach ($organizations as $org) {
            $org['status'] = 'active';
            $org['created_at'] = $now;
            $org['updated_at'] = $now;
            $this->db->table('organizations')->ignore(true)->insert($org);
        }

        // Devices
        $devices = [
            ['id' => 1, 'code' => 'DEV-POS-01', 'name' => 'POS Hà Nội', 'type' => 'pos', 'branch_id' => 1],
            ['id' => 2, 'code' => 'DEV-POS-02', 'name' => 'POS HCM', 'type' => 'pos', 'branch_id' => 2],
        ];
        foreach ($devices as $device) {
            $device['status'] = 'active';
            $device['created_at'] = $now;
            $device['updated_at'] = $now;
            $this->db->table('devices')->ignore(true)->insert($device);
        }
    }
}
