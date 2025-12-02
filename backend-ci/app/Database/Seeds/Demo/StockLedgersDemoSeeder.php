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
        $products = [
            ['product_id' => 501, 'variant_id' => 7001, 'qty' => 25],
            ['product_id' => 501, 'variant_id' => 7002, 'qty' => 15],
            ['product_id' => 502, 'variant_id' => null, 'qty' => 40],
            ['product_id' => 503, 'variant_id' => null, 'qty' => 35],
        ];

        foreach ($branches as $branchId) {
            foreach ($products as $idx => $row) {
                $refId = ($branchId * 1000) + ($idx + 1);
                $ledger->record([
                    'product_id' => $row['product_id'],
                    'variant_id' => $row['variant_id'],
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouses[$branchId] ?? null,
                    'batch_id' => null,
                    'movement_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                    'reference_type' => 'demo_opening',
                    'reference_id' => $refId,
                    'reference_seq' => 1,
                    'qty_delta' => $row['qty'],
                    'unit_cost' => $this->unitCost($row['product_id']),
                ]);
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
            $this->db->table('stock_bins')->whereIn('product_id', [501, 502, 503])->delete();
        }
    }
}
