<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockDisposalRepository;
use RuntimeException;

/**
 * Stock Disposal Service.
 * 
 * @agent-service: Stock disposal
 * @agent-pattern: Business logic layer
 */
class StockDisposalService
{
    protected StockDisposalRepository $repo;

    public function __construct(?StockDisposalRepository $repo = null)
    {
        $this->repo = $repo ?? new StockDisposalRepository();
    }

    /**
     * List disposals with pagination.
     * @agent-use: GET /api/inventory/disposals
     */
    public function list(array $filters): array
    {
        $rows = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);
        $summary = $this->repo->getSummary($filters);

        $limit = (int) ($filters['limit'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);
        $totalPages = (int) ceil($total / $limit);

        // Transform rows to frontend format
        $data = array_map([$this, 'transformRow'], $rows);

        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'totals' => [
                'total_quantity' => (float) ($summary['total_quantity'] ?? 0),
                'total_value' => (float) ($summary['total_value'] ?? 0),
            ],
        ];
    }

    /**
     * Show disposal detail.
     * @agent-use: GET /api/inventory/disposals/{code}
     */
    public function show($codeOrId): array
    {
        $row = is_numeric($codeOrId)
            ? $this->repo->findById((int) $codeOrId)
            : $this->repo->findByCode($codeOrId);

        if (!$row) {
            throw new RuntimeException('Không tìm thấy phiếu xuất hủy');
        }

        $items = $this->repo->findItems((int) $row['id']);
        $data = $this->transformRow($row);
        $data['items'] = array_map([$this, 'transformItem'], $items);

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Create new disposal.
     * @agent-use: POST /api/inventory/disposals
     */
    public function create(array $input): array
    {
        $branchId = $input['branch_id'] ?? getCurrentBranchId();
        if (!$branchId || !is_numeric($branchId)) {
            throw new \InvalidArgumentException('branch_id is required and must be a valid integer');
        }
        $branchId = (int) $branchId;
        
        $code = $this->repo->nextCode();

        $data = [
            'code' => $code,
            'branch_id' => $branchId,
            'status' => 'draft',
            'notes' => $input['notes'] ?? null,
            'created_by' => $input['created_by'] ?? getCurrentUserId(),
            'disposed_at' => $input['disposed_at'] ?? date('Y-m-d H:i:s'),
        ];

        $items = [];
        foreach (($input['items'] ?? []) as $item) {
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity' => (float) ($item['quantity'] ?? 0),
                'cost_price' => (float) ($item['cost_price'] ?? 0),
            ];
        }

        $disposal = $this->repo->create($data, $items);
        
        return [
            'success' => true,
            'data' => $this->transformRow($disposal),
        ];
    }

    /**
     * Update disposal.
     * @agent-use: PUT /api/inventory/disposals/{id}
     */
    public function update(int $id, array $input): array
    {
        $row = $this->require($id);
        
        if ($row['status'] !== 'draft') {
            throw new RuntimeException('Chỉ có thể sửa phiếu tạm');
        }

        $data = [];
        if (isset($input['notes'])) {
            $data['notes'] = $input['notes'];
        }
        if (isset($input['disposed_at'])) {
            $data['disposed_at'] = $input['disposed_at'];
        }
        if (isset($input['executor_id'])) {
            $data['executor_id'] = (int) $input['executor_id'];
        }

        if (!empty($data)) {
            $this->repo->update($id, $data);
        }

        if (isset($input['items'])) {
            $items = [];
            foreach ($input['items'] as $item) {
                $items[] = [
                    'product_id' => (int) $item['product_id'],
                    'variant_id' => !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
                    'sku' => $item['sku'] ?? '',
                    'name' => $item['name'] ?? '',
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'cost_price' => (float) ($item['cost_price'] ?? 0),
                ];
            }
            $this->repo->updateItems($id, $items);
        }

        return $this->show($id);
    }

    /**
     * Complete disposal.
     * @agent-use: POST /api/inventory/disposals/{id}/complete
     */
    public function complete(int $id, array $input): array
    {
        $row = $this->require($id);
        
        if ($row['status'] !== 'draft') {
            throw new RuntimeException('Phiếu đã được xử lý');
        }

        $this->repo->update($id, [
            'status' => 'completed',
            'disposed_at' => $input['disposed_at'] ?? date('Y-m-d H:i:s'),
            'executor_id' => $input['executor_id'] ?? getCurrentUserId(),
        ]);

        // TODO: Deduct inventory when completing disposal

        return $this->show($id);
    }

    /**
     * Cancel disposal.
     * @agent-use: POST /api/inventory/disposals/{id}/cancel
     */
    public function cancel(int $id, array $input): array
    {
        $row = $this->require($id);
        
        if ($row['status'] === 'completed') {
            throw new RuntimeException('Không thể hủy phiếu đã hoàn thành');
        }

        $this->repo->update($id, [
            'status' => 'cancelled',
            'notes' => $input['notes'] ?? $row['notes'],
        ]);

        return $this->show($id);
    }

    /**
     * Transform row to frontend format.
     */
    protected function transformRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'dispose_code' => $row['code'],
            'branch_id' => (int) $row['branch_id'],
            'branch_name' => $row['branch_name'] ?? null,
            'status' => $row['status'],
            'disposed_at' => $row['disposed_at'],
            'created_at' => $row['created_at'],
            'total_quantity' => (float) ($row['total_quantity'] ?? 0),
            'total_value' => (float) ($row['total_value'] ?? 0),
            'notes' => $row['notes'] ?? '',
            'created_by' => !empty($row['created_by']) ? (int) $row['created_by'] : null,
            'creator_name' => $row['creator_name'] ?? null,
            'executor_id' => !empty($row['executor_id']) ? (int) $row['executor_id'] : null,
            'executor_name' => $row['executor_name'] ?? null,
        ];
    }

    /**
     * Transform item to frontend format.
     */
    protected function transformItem(array $item): array
    {
        return [
            'id' => (int) $item['id'],
            'product_id' => (int) $item['product_id'],
            'variant_id' => !empty($item['variant_id']) ? (int) $item['variant_id'] : null,
            'sku' => $item['sku'] ?? '',
            'name' => $item['name'] ?? '',
            'quantity' => (float) ($item['quantity'] ?? 0),
            'cost_price' => (float) ($item['cost_price'] ?? 0),
            'disposal_value' => (float) ($item['disposal_value'] ?? 0),
        ];
    }

    /**
     * Require disposal exists.
     */
    protected function require(int $id): array
    {
        $row = $this->repo->findById($id);
        if (!$row) {
            throw new RuntimeException('Không tìm thấy phiếu xuất hủy');
        }
        return $row;
    }
}

/**
 * Helper to get current user ID from session/token.
 */
function getCurrentUserId(): ?int
{
    $session = session();
    return $session->get('user_id') ?? null;
}

/**
 * Helper to get current branch ID from session/token.
 */
function getCurrentBranchId(): ?int
{
    $session = session();
    return $session->get('branch_id') ?? null;
}
