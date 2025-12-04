<?php

namespace App\Database\Seeds\Demo;

use App\Services\Inventory\StockLedgerService;
use CodeIgniter\Database\Seeder;

/**
 * Seed stock_ledgers + stock_bins demo data aligned with orders/deliveries/returns.
 *
 * @agent-seeder: Demo stock ledgers
 * @agent-pattern: Idempotent ledger + bin adjustments
 * @agent-reusable: MEDIUM
 */
class StockLedgersDemoSeeder extends Seeder
{
    private array $costMap = [];

    public function run(): void
    {
        if (! $this->db->tableExists('stock_ledgers')) {
            return;
        }

        echo "   → Demo stock ledgers...\n";

        $this->costMap = $this->loadCosts();
        $this->cleanupExisting();

        $ledger = new StockLedgerService();
        $warehouses = DemoOrderHelper::warehousesByBranch($this->db);

        $this->seedOpeningBalances($ledger, $warehouses);
        $this->seedDeliveries($ledger, $warehouses);
        $this->seedReturns($ledger, $warehouses);
    }

    private function seedOpeningBalances(StockLedgerService $ledger, array $warehouses): void
    {
        $branches = [1, 2, 3, 4, 5];
        
        // Fetch all products
        $allProducts = $this->db->table('products')->select('id')->get()->getResultArray();
        $products = [];
        foreach ($allProducts as $p) {
            $products[] = ['product_id' => $p['id'], 'variant_id' => null, 'qty' => 1000];
        }

        // Fetch all variants
        if ($this->db->tableExists('product_variants')) {
             $allVariants = $this->db->table('product_variants')->select('id, product_id')->get()->getResultArray();
             foreach ($allVariants as $v) {
                 $products[] = ['product_id' => $v['product_id'], 'variant_id' => $v['id'], 'qty' => 1000];
             }
        }

        $binData = [];
        $ledgerData = [];
        $now = date('Y-m-d H:i:s');
        $movementDate = date('Y-m-d H:i:s', strtotime('-5 days'));

        foreach ($branches as $branchId) {
            foreach ($products as $idx => $row) {
                $refId = ($branchId * 100000) + ($idx + 1);
                $unitCost = $this->unitCost((int)$row['product_id']);
                
                $pId = (int)$row['product_id'];
                $vId = $row['variant_id'] ? (int)$row['variant_id'] : null;
                $bId = (int)$branchId;

                // Prepare Bin Data
                $binData[] = [
                    'product_id' => $pId,
                    'variant_id' => $vId,
                    'branch_id' => $bId,
                    'batch_id' => null,
                    'on_hand_qty' => $row['qty'],
                    'reserved_qty' => 0,
                    'updated_at' => $now,
                ];

                // Prepare Ledger Data
                $ledgerData[] = [
                    'product_id' => $pId,
                    'variant_id' => $vId,
                    'branch_id' => $bId,
                    'warehouse_id' => $warehouses[$bId] ?? null,
                    'batch_id' => null,
                    'movement_date' => $movementDate,
                    'reference_type' => 'demo_opening',
                    'reference_id' => $refId,
                    'reference_seq' => 1,
                    'qty_delta' => $row['qty'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $unitCost * $row['qty'],
                    'created_at' => $now,
                ];
            }
        }

        echo "      → Inserting " . count($binData) . " bin records...\n";

        // Chunk insert to avoid query size limits
        if (!empty($binData)) {
            $chunks = array_chunk($binData, 1000);
            foreach ($chunks as $chunk) {
                $this->db->table('stock_bins')->insertBatch($chunk);
            }
        }

        if (!empty($ledgerData)) {
            $chunks = array_chunk($ledgerData, 1000);
            foreach ($chunks as $chunk) {
                $this->db->table('stock_ledgers')->insertBatch($chunk);
            }
        }
    }

    private function seedDeliveries(StockLedgerService $ledger, array $warehouses): void
    {
        if (! $this->db->tableExists('delivery_note_items') || ! $this->db->tableExists('delivery_notes')) {
            return;
        }

        $rows = $this->db->table('delivery_note_items dni')
            ->select('dni.*, dn.id as delivery_note_id, dn.branch_id, dn.delivery_date, dn.delivery_number')
            ->join('delivery_notes dn', 'dn.id = dni.delivery_note_id', 'left')
            ->like('dn.delivery_number', 'DN-DEMO-', 'after')
            ->where('dni.delivered_quantity >', 0)
            ->get()
            ->getResultArray();

        $seqByNote = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId <= 0) {
                continue;
            }
            $movementDate = $row['delivery_date'] ? $row['delivery_date'] . ' 12:00:00' : date('Y-m-d H:i:s');
            $seqByNote[$row['delivery_note_id']] = ($seqByNote[$row['delivery_note_id']] ?? 0) + 1;
            $ledger->record([
                'product_id' => (int) $row['product_id'],
                'variant_id' => $row['variant_id'] ? (int) $row['variant_id'] : null,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouses[$branchId] ?? null,
                'batch_id' => null,
                'movement_date' => $movementDate,
                'reference_type' => 'demo_delivery',
                'reference_id' => (int) $row['delivery_note_id'],
                'reference_seq' => $seqByNote[$row['delivery_note_id']],
                'qty_delta' => -1 * (float) $row['delivered_quantity'],
                'unit_cost' => $this->unitCost((int) $row['product_id']),
            ]);
        }
    }

    private function seedReturns(StockLedgerService $ledger, array $warehouses): void
    {
        if (! $this->db->tableExists('return_items') || ! $this->db->tableExists('returns')) {
            return;
        }

        $rows = $this->db->table('return_items ri')
            ->select('ri.*, r.id as return_id, r.return_number, r.status, o.branch_id, oi.product_id, oi.variant_id')
            ->join('returns r', 'r.id = ri.return_id', 'inner')
            ->join('orders o', 'o.id = r.order_id', 'left')
            ->join('order_items oi', 'oi.id = ri.order_item_id', 'left')
            ->like('r.return_number', 'RET-DEMO-', 'after')
            ->whereIn('r.status', ['approved', 'completed'])
            ->get()
            ->getResultArray();

        $seqByReturn = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId <= 0) {
                continue;
            }
            $seqByReturn[$row['return_id']] = ($seqByReturn[$row['return_id']] ?? 0) + 1;
            $ledger->record([
                'product_id' => (int) $row['product_id'],
                'variant_id' => $row['variant_id'] ? (int) $row['variant_id'] : null,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouses[$branchId] ?? null,
                'batch_id' => null,
                'movement_date' => date('Y-m-d H:i:s'),
                'reference_type' => 'demo_return',
                'reference_id' => (int) $row['return_id'],
                'reference_seq' => $seqByReturn[$row['return_id']],
                'qty_delta' => (float) $row['quantity_returned'],
                'unit_cost' => $this->unitCost((int) $row['product_id']),
            ]);
        }
    }

    private function unitCost(int $productId): float
    {
        return $this->costMap[$productId] ?? 0;
    }

    private function loadCosts(): array
    {
        $map = [];
        if (! $this->db->tableExists('products')) {
            return $map;
        }
        $rows = $this->db->table('products')->select('id, purchase_price')->get()->getResultArray();
        foreach ($rows as $row) {
            $map[(int) $row['id']] = (float) ($row['purchase_price'] ?? 0);
        }
        return $map;
    }

    private function cleanupExisting(): void
    {
        $refTypes = ['demo_opening', 'demo_delivery', 'demo_return'];
        $this->db->table('stock_ledgers')->whereIn('reference_type', $refTypes)->delete();

        if ($this->db->tableExists('stock_bins')) {
            // Truncate to ensure we start fresh with our opening balances
            $this->db->table('stock_bins')->truncate();
        }
    }
}
