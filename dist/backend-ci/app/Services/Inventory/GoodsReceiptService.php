<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\GoodsReceiptRepository;
use App\Repositories\PurchaseOrders\PurchaseOrderRepository;
use App\Validators\GoodsReceiptValidator;
use RuntimeException;

/**
 * @agent-service: Goods receipts
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class GoodsReceiptService
{
    protected GoodsReceiptRepository $repo;
    protected PurchaseOrderRepository $poRepo;
    protected GoodsReceiptValidator $validator;
    protected StockLedgerService $ledger;

    public function __construct(
        ?GoodsReceiptRepository $repo = null,
        ?PurchaseOrderRepository $poRepo = null,
        ?GoodsReceiptValidator $validator = null,
        ?StockLedgerService $ledger = null
    ) {
        $this->repo = $repo ?? new GoodsReceiptRepository();
        $this->poRepo = $poRepo ?? new PurchaseOrderRepository();
        $this->validator = $validator ?? new GoodsReceiptValidator();
        $this->ledger = $ledger ?? new StockLedgerService();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $po = $data['purchase_order_id'] ? $this->poRepo->findById((int) $data['purchase_order_id']) : null;
        if ($data['purchase_order_id'] && ! $po) {
            throw new RuntimeException('Purchase order not found');
        }
        $number = $this->repo->nextNumber();
        $receiptRow = [
            'receipt_number' => $number,
            'purchase_order_id' => $data['purchase_order_id'],
            'branch_id' => $data['branch_id'] ?? ($po['branch_id'] ?? null),
            'status' => 'submitted',
        ];
        $receipt = $this->repo->create($receiptRow, $data['items']);

        if ($po) {
            $receivedMap = [];
            foreach ($po['items'] as $item) {
                $receivedMap[$item['id']] = $item['received_quantity'];
            }
            foreach ($receipt['items'] as $item) {
                foreach ($po['items'] as $poItem) {
                    if ($poItem['product_id'] === $item['product_id']) {
                        $receivedMap[$poItem['id']] = $poItem['received_quantity'] + $item['quantity'];
                    }
                }
            }
            $this->poRepo->updateReceivedQty($po['id'], $receivedMap);
            $this->poRepo->updateStatus($po['id'], 'received');
        }

        foreach ($receipt['items'] as $idx => $item) {
            $this->ledger->record([
                'product_id' => $item['product_id'],
                'variant_id' => null,
                'branch_id' => $receipt['branch_id'] ?? 0,
                'batch_id' => null,
                'qty_delta' => $item['quantity'],
                'reference_type' => 'goods_receipt',
                'reference_id' => $receipt['id'],
                'reference_seq' => $idx + 1,
            ]);
        }

        return ['success' => true, 'data' => $receipt];
    }

    public function get(int $id): array
    {
        $receipt = $this->repo->findById($id);
        if (! $receipt) {
            throw new RuntimeException('Goods receipt not found');
        }
        return ['success' => true, 'data' => $receipt];
    }
}
