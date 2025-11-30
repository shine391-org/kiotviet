<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockReconciliationRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Validators\StockReconciliationValidator;
use RuntimeException;

/**
 * Stock reconciliation flow.
 *
 * @agent-service: Stock reconciliation
 * @agent-pattern: State + variance posting
 * @agent-reusable: MEDIUM
 */
class StockReconciliationService
{
    protected StockReconciliationRepository $repo;
    protected StockReconciliationValidator $validator;
    protected StockLedgerService $ledger;
    protected StockBinRepository $bins;

    public function __construct(
        ?StockReconciliationRepository $repo = null,
        ?StockReconciliationValidator $validator = null,
        ?StockLedgerService $ledger = null,
        ?StockBinRepository $bins = null
    ) {
        $this->repo = $repo ?? new StockReconciliationRepository();
        $this->validator = $validator ?? new StockReconciliationValidator();
        $this->ledger = $ledger ?? new StockLedgerService();
        $this->bins = $bins ?? new StockBinRepository();
    }

    /** List. @agent-use: GET /api/stock-reconciliations */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    /** Detail. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** Create draft. */
    public function create(array $input): array
    {
        $payload = $this->validator->validateCreate($input);
        $number = $this->nextNumber((int) $payload['branch_id']);
        $recon = $this->repo->create([
            'recon_number' => $number,
            'branch_id' => $payload['branch_id'],
            'status' => 'draft',
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ], $payload['items']);

        return ['success' => true, 'data' => $this->require($recon['id'])];
    }

    /** Submit (set status submitted). */
    public function submit(int $id): array
    {
        $recon = $this->require($id);
        if ($recon['status'] !== 'draft') {
            throw new RuntimeException('Only draft can be submitted');
        }
        $this->repo->updateStatus($id, 'submitted');
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** Approve -> post ledger variance. */
    public function approve(int $id, array $input): array
    {
        $recon = $this->require($id);
        if (! in_array($recon['status'], ['draft', 'submitted'], true)) {
            throw new RuntimeException('Cannot approve in current status');
        }
        $this->validator->validateApprove($input);
        $branchId = (int) $recon['branch_id'];
        $db = $this->repo->db();
        $db->transBegin();
        try {
            foreach ($recon['items'] as $item) {
                $bin = $this->bins->lockBin((int) $item['product_id'], $item['variant_id'] ? (int) $item['variant_id'] : null, $branchId, $item['batch_id'] ? (int) $item['batch_id'] : null);
                $current = (float) ($bin['on_hand_qty'] ?? 0);
                $variance = (float) $item['counted_qty'] - $current;
                $this->repo->updateItemVariance((int) $item['id'], $current, $variance);
                if (abs($variance) < 0.0001) {
                    continue;
                }
                $this->ledger->record([
                    'product_id' => (int) $item['product_id'],
                    'variant_id' => $item['variant_id'] ? (int) $item['variant_id'] : null,
                    'branch_id' => $branchId,
                    'batch_id' => $item['batch_id'] ? (int) $item['batch_id'] : null,
                    'movement_date' => date('Y-m-d H:i:s'),
                    'reference_type' => 'stock_recon',
                    'reference_id' => $id,
                    'reference_seq' => (int) $item['id'],
                    'qty_delta' => $variance,
                    'unit_cost' => (float) ($item['unit_cost'] ?? 0),
                ]);
            }
            $this->repo->updateStatus($id, 'approved', [
                'approved_by' => $input['approved_by'] ?? null,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $this->require($id)];
    }

    public function reject(int $id, array $input): array
    {
        $recon = $this->require($id);
        if ($recon['status'] === 'approved') {
            throw new RuntimeException('Cannot reject approved reconciliation');
        }
        $notes = $input['notes'] ?? ($recon['notes'] ?? null);
        $rejectedBy = $input['rejected_by'] ?? null;
        $this->repo->updateStatus($id, 'rejected', [
            'notes' => $notes,
            'rejected_by' => $rejectedBy,
        ]);
        return ['success' => true, 'data' => $this->require($id)];
    }

    private function nextNumber(int $branchId): string
    {
        return 'RECON-' . $branchId . '-' . date('YmdHis');
    }

    private function require(int $id): array
    {
        $row = $this->repo->find($id);
        if (! $row) {
            throw new RuntimeException('Reconciliation not found');
        }
        return $row;
    }
}
