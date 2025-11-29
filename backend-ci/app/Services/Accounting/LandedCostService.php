<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\LandedCostRepository;
use App\Repositories\Inventory\GoodsReceiptRepository;
use App\Validators\LandedCostValidator;
use RuntimeException;

/**
 * @agent-service: Landed cost
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class LandedCostService
{
    protected LandedCostRepository $repo;
    protected LandedCostValidator $validator;
    protected GoodsReceiptRepository $grns;

    public function __construct(
        ?LandedCostRepository $repo = null,
        ?LandedCostValidator $validator = null,
        ?GoodsReceiptRepository $grns = null
    ) {
        $this->repo = $repo ?? new LandedCostRepository();
        $this->validator = $validator ?? new LandedCostValidator();
        $this->grns = $grns ?? new GoodsReceiptRepository();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $grn = $this->grns->findById($data['goods_receipt_id']);
        if (! $grn) {
            throw new RuntimeException('Goods receipt not found');
        }
        $total = array_sum(array_column($data['items'], 'amount'));
        $voucher = [
            'voucher_number' => $this->repo->nextNumber(),
            'goods_receipt_id' => $data['goods_receipt_id'],
            'total_cost' => $total,
            'status' => 'submitted',
        ];
        $res = $this->repo->create($voucher, $data['items']);
        return ['success' => true, 'data' => $res];
    }

    public function get(int $id): array
    {
        $voucher = $this->repo->findById($id);
        if (! $voucher) {
            throw new RuntimeException('Landed cost voucher not found');
        }
        return ['success' => true, 'data' => $voucher];
    }
}
