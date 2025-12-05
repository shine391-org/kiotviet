<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockEntryRepository;
use App\Repositories\Inventory\InventoryRepository;
use App\Validators\StockEntryValidator;
use RuntimeException;

/**
 * Stock entry orchestration (issue/receipt/transfer/return).
 *
 * @agent-service: Stock entry
 * @agent-pattern: State + ledger posting
 * @agent-reusable: HIGH
 */
class StockEntryService
{
    protected StockEntryRepository $repo;
    protected StockEntryValidator $validator;
    protected StockLedgerService $ledger;
    protected InventoryRepository $inventoryRepo;

    public function __construct(
        ?StockEntryRepository $repo = null,
        ?StockEntryValidator $validator = null,
        ?StockLedgerService $ledger = null,
        ?InventoryRepository $inventoryRepo = null
    ) {
        $this->repo = $repo ?? new StockEntryRepository();
        $this->validator = $validator ?? new StockEntryValidator();
        $this->ledger = $ledger ?? new StockLedgerService();
        $this->inventoryRepo = $inventoryRepo ?? new InventoryRepository();
    }

    /**
     * Create draft stock entry.
     *
     * @agent-use: POST /api/stock-entries
     * @agent-pattern: Validation -> repo create
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $number = $this->repo->nextNumber($data['type']);
        $entryRow = [
            'entry_number' => $number,
            'type' => $data['type'],
            'status' => 'draft',
            'branch_id' => $data['branch_id'] ?? null,
            'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
            'target_warehouse_id' => $data['target_warehouse_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'return_reason' => $data['return_reason'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ];

        $created = $this->repo->create($entryRow, $data['items']);
        return ['success' => true, 'data' => $created];
    }

    /**
     * Submit entry and post to ledger/bins.
     *
     * @agent-use: POST /api/stock-entries/{id}/submit
     * @agent-pattern: Status transition with side effects
     */
    public function submit(int $id): array
    {
        $entry = $this->requireEntry($id);
        if ($entry['status'] === 'submitted') {
            return ['success' => true, 'data' => $entry];
        }
        if ($entry['status'] === 'cancelled') {
            throw new RuntimeException('Cannot submit cancelled entry');
        }

        $db = $this->repo->db();
        $db->transBegin();
        try {
            $this->postMovements($entry, false);
            $updated = $this->repo->updateStatus($id, 'submitted', [
                'submitted_at' => date('Y-m-d H:i:s'),
                'submitted_by' => $entry['created_by'] ?? null,
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $updated];
    }

    /**
     * Cancel submitted entry and reverse movements.
     *
     * @agent-use: POST /api/stock-entries/{id}/cancel
     * @agent-pattern: Reversal posting
     */
    public function cancel(int $id): array
    {
        $entry = $this->requireEntry($id);
        if ($entry['status'] !== 'submitted') {
            throw new RuntimeException('Only submitted entries can be cancelled');
        }

        $db = $this->repo->db();
        $db->transBegin();
        try {
            $this->postMovements($entry, true);
            $updated = $this->repo->updateStatus($id, 'cancelled', [
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => $entry['created_by'] ?? null,
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $updated];
    }

    /** Get detail. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireEntry($id)];
    }

    private function postMovements(array $entry, bool $reverse): void
    {
        $multiplier = $reverse ? -1 : 1;
        $type = $entry['type'];
        foreach ($entry['items'] as $index => $item) {
            $qty = (float) $item['qty'];
            if ($type === 'issue') {
                $sourceBranch = $this->resolveBranchId($item['source_branch_id'] ?? null, $item['source_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                $this->recordMovement($entry, $item, $index + 1, -1 * $qty * $multiplier, $sourceBranch, $item['source_warehouse_id'] ?? null, $reverse);
            } elseif ($type === 'receipt') {
                $targetBranch = $this->resolveBranchId($item['target_branch_id'] ?? null, $item['target_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                $this->recordMovement($entry, $item, $index + 1, $qty * $multiplier, $targetBranch, $item['target_warehouse_id'] ?? null, $reverse);
            } elseif ($type === 'transfer') {
                $seq = ($index * 2) + 1;
                $sourceBranch = $this->resolveBranchId($item['source_branch_id'] ?? null, $item['source_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                $targetBranch = $this->resolveBranchId($item['target_branch_id'] ?? null, $item['target_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                $this->recordMovement($entry, $item, $seq, -1 * $qty * $multiplier, $sourceBranch, $item['source_warehouse_id'] ?? null, $reverse);
                $this->recordMovement($entry, $item, $seq + 1, $qty * $multiplier, $targetBranch, $item['target_warehouse_id'] ?? null, $reverse);
            } elseif ($type === 'return') {
                $seq = ($index * 2) + 1;
                $targetBranch = $this->resolveBranchId($item['target_branch_id'] ?? null, $item['target_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                $this->recordMovement($entry, $item, $seq, $qty * $multiplier, $targetBranch, $item['target_warehouse_id'] ?? null, $reverse);
                if (! empty($item['source_warehouse_id']) || ! empty($item['source_branch_id'])) {
                    $sourceBranch = $this->resolveBranchId($item['source_branch_id'] ?? null, $item['source_warehouse_id'] ?? null, $entry['branch_id'] ?? null);
                    $this->recordMovement($entry, $item, $seq + 1, -1 * $qty * $multiplier, $sourceBranch, $item['source_warehouse_id'] ?? null, $reverse);
                }
            }
        }
    }

    private function recordMovement(array $entry, array $item, int $seq, float $qtyDelta, int $branchId, ?int $warehouseId, bool $reverse): void
    {
        $referenceType = $reverse ? 'stock_entry_cancel' : 'stock_entry';
        $this->ledger->record([
            'product_id' => (int) $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'batch_id' => $item['batch_id'] ?? null,
            'serial_number' => $item['serial_number'] ?? null,
            'movement_date' => date('Y-m-d H:i:s'),
            'reference_type' => $referenceType,
            'reference_id' => (int) $entry['id'],
            'reference_seq' => $seq,
            'qty_delta' => $qtyDelta,
            'unit_cost' => 0,
        ]);
    }

    private function resolveBranchId(?int $explicit, ?int $warehouseId, ?int $fallback): int
    {
        if ($explicit && $explicit > 0) {
            return $explicit;
        }
        if ($warehouseId) {
            $warehouse = $this->inventoryRepo->warehouseById($warehouseId);
            if ($warehouse && ! empty($warehouse['branch_id'])) {
                return (int) $warehouse['branch_id'];
            }
        }
        if ($fallback && $fallback > 0) {
            return $fallback;
        }
        throw new RuntimeException('Branch is required to post stock entry');
    }

    private function requireEntry(int $id): array
    {
        $row = $this->repo->findById($id);
        if (! $row) {
            throw new RuntimeException('Stock entry not found');
        }
        return $row;
    }
}
