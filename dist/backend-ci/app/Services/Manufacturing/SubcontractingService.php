<?php

namespace App\Services\Manufacturing;

use App\Repositories\Manufacturing\SubcontractingRepository;
use App\Services\Inventory\StockLedgerService;
use App\Validators\SubcontractingValidator;
use RuntimeException;

/**
 * @agent-service: Subcontracting
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class SubcontractingService
{
    protected SubcontractingRepository $repo;
    protected SubcontractingValidator $validator;
    protected StockLedgerService $ledger;

    public function __construct(
        ?SubcontractingRepository $repo = null,
        ?SubcontractingValidator $validator = null,
        ?StockLedgerService $ledger = null
    ) {
        $this->repo = $repo ?? new SubcontractingRepository();
        $this->validator = $validator ?? new SubcontractingValidator();
        $this->ledger = $ledger ?? new StockLedgerService();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $order = [
            'order_number' => $this->repo->nextNumber(),
            'supplier_id' => $data['supplier_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'status' => 'draft',
        ];
        $created = $this->repo->create($order, $data['materials']);
        return ['success' => true, 'data' => $created];
    }

    public function issueMaterials(int $id): array
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            throw new RuntimeException('Subcontracting order not found');
        }
        $map = [];
        foreach ($order['materials'] as $mat) {
            $map[$mat['id']] = $mat['quantity'];
        }
        $this->repo->markIssued($map);
        $this->repo->updateStatus($id, 'materials_issued');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function receiveProduct(int $id): array
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            throw new RuntimeException('Subcontracting order not found');
        }
        $this->ledger->record([
            'product_id' => $order['product_id'],
            'variant_id' => null,
            'branch_id' => 1,
            'batch_id' => null,
            'qty_delta' => $order['quantity'],
            'reference_type' => 'subcontracting_receipt',
            'reference_id' => $order['id'],
            'reference_seq' => 1,
        ]);
        $this->repo->updateStatus($id, 'completed');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }
}
