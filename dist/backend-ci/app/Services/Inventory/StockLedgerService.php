<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockBinRepository;
use App\Repositories\Inventory\StockLedgerRepository;
use App\Validators\StockLedgerValidator;
use RuntimeException;

/**
 * Stock ledger service with bin update.
 *
 * @agent-service: Stock ledger
 * @agent-pattern: Atomic ledger+bin
 * @agent-reusable: MEDIUM
 */
class StockLedgerService
{
    protected StockLedgerRepository $ledger;
    protected StockBinRepository $bins;
    protected StockLedgerValidator $validator;

    public function __construct(
        ?StockLedgerRepository $ledger = null,
        ?StockBinRepository $bins = null,
        ?StockLedgerValidator $validator = null
    ) {
        $this->ledger = $ledger ?? new StockLedgerRepository();
        $this->bins = $bins ?? new StockBinRepository();
        $this->validator = $validator ?? new StockLedgerValidator();
    }

    /**
     * Record ledger entry and update bin atomically.
     * Idempotent by reference_type/reference_id/reference_seq.
     */
    public function record(array $input): array
    {
        $data = $this->validator->validateRecord($input);
        $existing = $this->ledger->findByReference($data['reference_type'], (int) $data['reference_id'], (int) $data['reference_seq']);
        if ($existing) {
            return ['success' => true, 'data' => $existing, 'message' => 'Already recorded'];
        }

        $db = $this->bins->db();
        $db->transBegin();
        try {
            $this->bins->adjust(
                (int) $data['product_id'],
                $data['variant_id'] ?? null,
                (int) $data['branch_id'],
                $data['batch_id'] ?? null,
                (float) $data['qty_delta'],
                0
            );
            $entry = $this->ledger->create($data);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $entry];
    }
}
