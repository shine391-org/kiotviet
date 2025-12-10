<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;

/**
 * Seeder for customer addresses and debt transactions.
 * Provides demo data for CustomerDetailPanel tabs.
 */
class CustomerDetailDemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "   → Customer detail demo data...\n";

        $this->seedCustomerAddresses();
        $this->seedCustomerDebtTransactions();
    }

    private function seedCustomerAddresses(): void
    {
        if (! $this->db->tableExists('customer_addresses')) {
            echo "      ⚠️  customer_addresses table not found\n";
            return;
        }

        // Get customers
        $customers = $this->db->table('customers')
            ->select('id, name, phone, address')
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        if (empty($customers)) {
            echo "      ⚠️  No customers found\n";
            return;
        }

        // Clean existing demo data
        $this->db->table('customer_addresses')
            ->like('name', 'Demo-', 'after')
            ->delete();

        $now = date('Y-m-d H:i:s');
        $addressNames = ['Nhà riêng', 'Văn phòng', 'Kho hàng', 'Chi nhánh'];
        $provinces = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng'];
        $districts = ['Quận 1', 'Quận 3', 'Quận 7', 'Cầu Giấy', 'Đống Đa', 'Hai Bà Trưng'];

        $rows = [];
        foreach ($customers as $idx => $customer) {
            // Each customer gets 1-3 addresses
            $addressCount = ($idx % 3) + 1;
            
            for ($i = 0; $i < $addressCount; $i++) {
                $rows[] = [
                    'customer_id' => (int) $customer['id'],
                    'name' => 'Demo-' . $addressNames[$i % count($addressNames)],
                    'recipient_name' => $customer['name'] ?? 'Khách hàng ' . $customer['id'],
                    'phone' => $customer['phone'] ?? ('098' . rand(1000000, 9999999)),
                    'address' => 'Số ' . ($idx + 1) * 10 . ', Đường ABC, ' . $districts[$i % count($districts)] . ', ' . $provinces[$idx % count($provinces)],
                    'province' => $provinces[$idx % count($provinces)],
                    'district' => $districts[$i % count($districts)],
                    'ward' => 'Phường ' . ($i + 1),
                    'is_default' => $i === 0 ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            $this->db->table('customer_addresses')->insertBatch($rows);
            echo "      ✓ Added " . count($rows) . " customer addresses\n";
        }
    }

    private function seedCustomerDebtTransactions(): void
    {
        if (! $this->db->tableExists('customer_debt_transactions')) {
            echo "      ⚠️  customer_debt_transactions table not found\n";
            return;
        }

        // Get demo customers (by code pattern) or any recent customers
        $customers = $this->db->table('customers')
            ->select('id, current_debt, code')
            ->where('deleted_at', null)
            ->like('code', 'CUST-DEMO-', 'after')  // Get CUST-DEMO-xxx customers
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // Fallback: if no CUST-DEMO customers, get any recent customers
        if (empty($customers)) {
            $customers = $this->db->table('customers')
                ->select('id, current_debt, code')
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        }

        if (empty($customers)) {
            echo "      ⚠️  No customers found\n";
            return;
        }

        // Clean existing demo data
        $this->db->table('customer_debt_transactions')
            ->like('code', 'CDT-DEMO-', 'after')
            ->delete();

        $rows = [];
        $counter = 1;

        // Create realistic scenarios for each customer
        foreach ($customers as $idx => $customer) {
            $customerId = (int) $customer['id'];
            $scenario = $idx % 4; // 4 different scenarios
            
            switch ($scenario) {
                case 0:
                    // Scenario 1: Invoice + Full Payment = 0 debt
                    $saleAmount = rand(500000, 2000000);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'SALE', $saleAmount, $saleAmount, $idx * 4 + 2);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'PAYMENT', -$saleAmount, 0, $idx * 4 + 1);
                    break;
                    
                case 1:
                    // Scenario 2: Invoice + Partial Payment = remaining debt  
                    $saleAmount = rand(800000, 3000000);
                    $paymentAmount = rand((int)($saleAmount * 0.3), (int)($saleAmount * 0.7));
                    $remaining = $saleAmount - $paymentAmount;
                    $rows[] = $this->createTransaction($customerId, $counter++, 'SALE', $saleAmount, $saleAmount, $idx * 4 + 2);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'PAYMENT', -$paymentAmount, $remaining, $idx * 4 + 1);
                    break;
                    
                case 2:
                    // Scenario 3: Multiple invoices + multiple payments
                    $sale1 = rand(300000, 800000);
                    $sale2 = rand(400000, 900000);
                    $total = $sale1 + $sale2;
                    $payment1 = rand((int)($total * 0.2), (int)($total * 0.4));
                    // Clamp payment2 to ensure payments don't exceed total
                    $maxPayment2 = max(0, $total - $payment1);
                    $payment2 = min(rand((int)($total * 0.1), (int)($total * 0.3)), $maxPayment2);
                    $remaining = max(0, $total - $payment1 - $payment2);
                    
                    // Calculate running balances correctly
                    $balanceAfterSale1 = $sale1;
                    $balanceAfterPayment1 = $sale1 - $payment1;
                    $balanceAfterSale2 = $balanceAfterPayment1 + $sale2;
                    $balanceAfterPayment2 = $remaining;
                    
                    $rows[] = $this->createTransaction($customerId, $counter++, 'SALE', $sale1, $balanceAfterSale1, $idx * 4 + 4);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'PAYMENT', -$payment1, $balanceAfterPayment1, $idx * 4 + 3);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'SALE', $sale2, $balanceAfterSale2, $idx * 4 + 2);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'PAYMENT', -$payment2, $balanceAfterPayment2, $idx * 4 + 1);
                    break;
                    
                case 3:
                    // Scenario 4: Single invoice, no payment (full debt)
                    $saleAmount = rand(600000, 1500000);
                    $rows[] = $this->createTransaction($customerId, $counter++, 'SALE', $saleAmount, $saleAmount, $idx * 4 + 1);
                    break;
            }
        }

        if (! empty($rows)) {
            $this->db->table('customer_debt_transactions')->insertBatch($rows);
            echo "      ✓ Added " . count($rows) . " customer debt transactions\n";
        }
    }

    private ?int $defaultUserId = null;
    private ?int $defaultBranchId = null;

    private function getDefaultUserId(): int
    {
        if ($this->defaultUserId === null) {
            $user = $this->db->table('users')
                ->select('id')
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->limit(1)
                ->get()
                ->getRowArray();
            
            $this->defaultUserId = $user ? (int) $user['id'] : 1;
        }
        return $this->defaultUserId;
    }

    private function getDefaultBranchId(): int
    {
        if ($this->defaultBranchId === null) {
            $branch = $this->db->table('branches')
                ->select('id')
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->limit(1)
                ->get()
                ->getRowArray();
            
            $this->defaultBranchId = $branch ? (int) $branch['id'] : 1;
        }
        return $this->defaultBranchId;
    }

    private function createTransaction(int $customerId, int $counter, string $type, float $value, float $balance, int $daysAgo): array
    {
        $createdAt = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
        return [
            'customer_id' => $customerId,
            'code' => sprintf('CDT-DEMO-%05d', $counter),
            'type' => $type,
            'value' => $value,
            'balance' => max(0, $balance),
            'notes' => 'Demo transaction ' . $type,
            'created_by' => $this->getDefaultUserId(),
            'branch_id' => $this->getDefaultBranchId(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
