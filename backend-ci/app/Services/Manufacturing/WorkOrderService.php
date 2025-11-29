<?php

namespace App\Services\Manufacturing;

use App\Repositories\Manufacturing\BOMRepository;
use App\Repositories\Manufacturing\WorkOrderRepository;
use App\Services\Inventory\StockLedgerService;
use App\Validators\WorkOrderValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Work order logic with stock consumption/production.
 *
 * @agent-service: Work orders
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class WorkOrderService
{
    protected WorkOrderRepository $repo;
    protected BOMRepository $boms;
    protected WorkOrderValidator $validator;
    protected StockLedgerService $ledger;

    public function __construct(
        ?WorkOrderRepository $repo = null,
        ?BOMRepository $boms = null,
        ?StockLedgerService $ledger = null,
        ?WorkOrderValidator $validator = null
    ) {
        $this->repo = $repo ?? new WorkOrderRepository();
        $this->boms = $boms ?? new BOMRepository();
        $this->ledger = $ledger ?? new StockLedgerService();
        $this->validator = $validator ?? new WorkOrderValidator();
    }

    /** List work orders. */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters), 'total' => $this->repo->count($filters)];
    }

    /** Show work order. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireWorkOrder($id)];
    }

    /** Create work order. */
    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        $bom = $this->requireBOM((int) $validated['bom_id']);
        if ((int) $bom['product_id'] !== (int) $validated['product_id']) {
            throw new InvalidArgumentException('BOM product mismatch');
        }
        if (empty($bom['is_active'])) {
            throw new InvalidArgumentException('BOM is inactive');
        }

        $wo = $this->repo->create([
            'product_id' => (int) $validated['product_id'],
            'bom_id' => (int) $validated['bom_id'],
            'branch_id' => (int) $validated['branch_id'],
            'quantity' => (float) $validated['quantity'],
            'status' => 'draft',
            'planned_start' => $validated['planned_start'] ?? null,
            'planned_end' => $validated['planned_end'] ?? null,
        ]);
        return ['success' => true, 'data' => $wo];
    }

    /** Release work order. */
    public function release(int $id): array
    {
        $wo = $this->requireWorkOrder($id);
        $this->assertStatus($wo, ['draft']);
        $updated = $this->repo->update($id, ['status' => 'released']);
        return ['success' => true, 'data' => $updated];
    }

    /** Start work order. */
    public function start(int $id): array
    {
        $wo = $this->requireWorkOrder($id);
        $this->assertStatus($wo, ['draft', 'released']);
        $now = date('Y-m-d H:i:s');
        $updated = $this->repo->update($id, ['status' => 'in_progress', 'actual_start' => $wo['actual_start'] ?? $now]);
        return ['success' => true, 'data' => $updated];
    }

    /** Complete work order: consume components and produce finished goods. */
    public function complete(int $id): array
    {
        $wo = $this->requireWorkOrder($id);
        $this->assertStatus($wo, ['in_progress', 'released']);

        $bom = $this->requireBOM((int) $wo['bom_id']);
        $qty = (float) $wo['quantity'];
        $branchId = (int) $wo['branch_id'];

        $seq = 1;
        foreach ($bom['items'] as $item) {
            $componentQty = ((float) $item['quantity']) * $qty;
            if ($componentQty <= 0) {
                continue;
            }
            $this->ledger->record([
                'product_id' => (int) $item['component_product_id'],
                'variant_id' => null,
                'branch_id' => $branchId,
                'batch_id' => null,
                'movement_date' => date('Y-m-d H:i:s'),
                'reference_type' => 'work_order',
                'reference_id' => $id,
                'reference_seq' => $seq++,
                'qty_delta' => -$componentQty,
                'unit_cost' => 0,
            ]);
        }

        // Produce finished good
        $this->ledger->record([
            'product_id' => (int) $wo['product_id'],
            'variant_id' => null,
            'branch_id' => $branchId,
            'batch_id' => null,
            'movement_date' => date('Y-m-d H:i:s'),
            'reference_type' => 'work_order',
            'reference_id' => $id,
            'reference_seq' => $seq,
            'qty_delta' => $qty,
            'unit_cost' => 0,
        ]);

        $updated = $this->repo->update($id, [
            'status' => 'completed',
            'actual_start' => $wo['actual_start'] ?? date('Y-m-d H:i:s'),
            'actual_end' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'data' => $updated];
    }

    /** Cancel work order. */
    public function cancel(int $id): array
    {
        $wo = $this->requireWorkOrder($id);
        $this->assertStatus($wo, ['draft', 'released', 'in_progress']);
        $updated = $this->repo->update($id, ['status' => 'cancelled']);
        return ['success' => true, 'data' => $updated];
    }

    private function assertStatus(array $wo, array $allowed): void
    {
        if (! in_array($wo['status'], $allowed, true)) {
            throw new InvalidArgumentException('Invalid status transition');
        }
    }

    private function requireWorkOrder(int $id): array
    {
        $wo = $this->repo->find($id);
        if (! $wo) {
            throw new RuntimeException('Work order not found');
        }
        return $wo;
    }

    private function requireBOM(int $id): array
    {
        $bom = $this->boms->findWithItems($id);
        if (! $bom) {
            throw new RuntimeException('BOM not found');
        }
        return $bom;
    }
}
