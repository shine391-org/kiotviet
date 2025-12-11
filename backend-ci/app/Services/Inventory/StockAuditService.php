<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockReconciliationRepository;
use App\Repositories\Products\ProductRepository;
use App\Repositories\Users\UserRepository;
use App\Validators\StockReconciliationValidator;
use RuntimeException;

/**
 * Stock audit service - transforms reconciliation data to match frontend expectations.
 *
 * Status mapping:
 * - draft (backend) -> draft (frontend)
 * - submitted (backend) -> draft (frontend)
 * - approved (backend) -> balanced (frontend)
 * - rejected (backend) -> cancelled (frontend)
 *
 * @agent-service: Stock audit
 * @agent-pattern: Facade over StockReconciliation
 * @agent-reusable: MEDIUM
 */
class StockAuditService
{
    protected StockReconciliationRepository $repo;
    protected StockReconciliationValidator $validator;
    protected StockLedgerService $ledger;
    protected ProductRepository $productRepo;
    protected UserRepository $userRepo;

    /** Status mapping from backend to frontend */
    protected const STATUS_MAP = [
        'draft' => 'draft',
        'submitted' => 'draft',
        'approved' => 'balanced',
        'rejected' => 'cancelled',
    ];

    /** Reverse status mapping from frontend to backend */
    protected const REVERSE_STATUS_MAP = [
        'draft' => 'draft',
        'balanced' => 'approved',
        'cancelled' => 'rejected',
    ];

    public function __construct(
        ?StockReconciliationRepository $repo = null,
        ?StockReconciliationValidator $validator = null,
        ?StockLedgerService $ledger = null,
        ?ProductRepository $productRepo = null,
        ?UserRepository $userRepo = null
    ) {
        $this->repo = $repo ?? new StockReconciliationRepository();
        $this->validator = $validator ?? new StockReconciliationValidator();
        $this->ledger = $ledger ?? new StockLedgerService();
        $this->productRepo = $productRepo ?? new ProductRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
    }

    /**
     * List stock audits for frontend.
     * @agent-use: GET /api/inventory/stock-audits
     * 
     * @todo Refactor to use repository-level pagination (SQL LIMIT/OFFSET) instead of in-memory pagination.
     * Current implementation fetches all rows then paginates in PHP, which doesn't scale well.
     */
    public function list(array $filters): array
    {
        // Map frontend status filters to backend statuses
        if (!empty($filters['statuses'])) {
            $backendStatuses = [];
            foreach ($filters['statuses'] as $status) {
                if ($status === 'draft') {
                    $backendStatuses[] = 'draft';
                    $backendStatuses[] = 'submitted';
                } elseif ($status === 'balanced') {
                    $backendStatuses[] = 'approved';
                } elseif ($status === 'cancelled') {
                    $backendStatuses[] = 'rejected';
                }
            }
            $filters['status'] = $backendStatuses;
            unset($filters['statuses']);
        }

        // Handle date filtering
        if (!empty($filters['dateMode'])) {
            $filters = $this->applyDateFilter($filters);
        }

        // Get data from repository
        $rows = $this->repo->list($filters);
        
        // Pre-fetch all user names to avoid N+1 queries
        $userIds = [];
        foreach ($rows as $row) {
            if (!empty($row['created_by'])) {
                $userIds[] = (int) $row['created_by'];
            }
            if (!empty($row['approved_by'])) {
                $userIds[] = (int) $row['approved_by'];
            }
        }
        $userMap = $this->fetchUserNames(array_unique($userIds));
        
        // Transform to frontend format with pre-fetched user names
        $data = array_map(fn($row) => $this->transformToFrontend($row, false, $userMap), $rows);
        
        // Apply pagination
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 15);
        $total = count($data);
        $totalPages = (int) ceil($total / $limit);
        
        // Sort if needed
        if (!empty($filters['sort'])) {
            $data = $this->applySorting($data, $filters['sort']);
        }
        
        // Slice for pagination
        $offset = ($page - 1) * $limit;
        $pageData = array_slice($data, $offset, $limit);
        
        // Calculate summary
        $summary = $this->calculateSummary($data);

        return [
            'success' => true,
            'data' => array_values($pageData),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'summary' => $summary,
        ];
    }

    /**
     * Show single audit.
     */
    public function show($code): array
    {
        // Code can be ID or recon_number
        $row = is_numeric($code) 
            ? $this->repo->find((int) $code)
            : $this->repo->findByCode($code);
            
        if (!$row) {
            throw new RuntimeException('Không tìm thấy phiếu kiểm kho');
        }

        return [
            'success' => true,
            'audit' => $this->transformToFrontend($row, true),
        ];
    }

    /**
     * Create new audit.
     */
    public function create(array $input): array
    {
        // Transform frontend items to backend format
        $input = $this->transformItemsFromFrontend($input);
        
        $payload = $this->validator->validateCreate($input);
        $branchId = (int) ($payload['branch_id'] ?? 1);
        $number = $this->nextNumber($branchId);
        
        $recon = $this->repo->create([
            'recon_number' => $number,
            'branch_id' => $branchId,
            'status' => 'draft',
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ], $payload['items'] ?? []);

        return [
            'success' => true,
            'data' => $this->transformToFrontend($this->repo->find($recon['id']), true),
        ];
    }
    
    /**
     * Transform frontend items (product_code) to backend format (product_id).
     */
    protected function transformItemsFromFrontend(array $input): array
    {
        if (empty($input['items'])) {
            return $input;
        }
        
        $transformedItems = [];
        
        foreach ($input['items'] as $item) {
            $productId = $item['product_id'] ?? null;
            
            // If product_code is provided, lookup product_id via ProductRepository
            if (!$productId && !empty($item['product_code'])) {
                $product = $this->productRepo->findByCode($item['product_code']);
                $productId = $product['id'] ?? null;
            }
            
            if (!$productId) {
                throw new RuntimeException("Không tìm thấy sản phẩm: " . ($item['product_code'] ?? 'unknown'));
            }
            
            $transformedItems[] = [
                'product_id' => (int) $productId,
                'variant_id' => $item['variant_id'] ?? null,
                'batch_id' => $item['batch_id'] ?? null,
                'counted_qty' => (float) ($item['counted_qty'] ?? 0),
                'current_qty' => (float) ($item['current_qty'] ?? 0),
                'unit_cost' => (float) ($item['unit_cost'] ?? 0),
                'remarks' => $item['remarks'] ?? null,
            ];
        }
        
        $input['items'] = $transformedItems;
        $input['branch_id'] = $input['branch_id'] ?? 1;
        
        return $input;
    }

    /**
     * Update draft audit.
     */
    public function update(int $id, array $input): array
    {
        $row = $this->require($id);
        if (!in_array($row['status'], ['draft', 'submitted'])) {
            throw new RuntimeException('Chỉ có thể sửa phiếu tạm');
        }

        // Update notes if provided
        if (isset($input['notes'])) {
            $this->repo->updateStatus($id, $row['status'], ['notes' => $input['notes']]);
        }

        // TODO: Update items if provided

        return [
            'success' => true,
            'data' => $this->transformToFrontend($this->repo->find($id), true),
        ];
    }

    /**
     * Complete audit - mark as balanced (approved).
     */
    public function complete(int $id, array $input): array
    {
        $row = $this->require($id);
        if (!in_array($row['status'], ['draft', 'submitted'])) {
            throw new RuntimeException('Phiếu đã được xử lý');
        }

        // Use the reconciliation service's approve logic
        $reconService = new StockReconciliationService($this->repo, $this->validator, $this->ledger);
        $result = $reconService->approve($id, [
            'approved_by' => $input['reconciled_by'] ?? $input['approved_by'] ?? null,
        ]);

        return [
            'success' => true,
            'data' => $this->transformToFrontend($result['data'], true),
        ];
    }

    /**
     * Cancel audit.
     */
    public function cancel(int $id, array $input): array
    {
        $row = $this->require($id);
        if ($row['status'] === 'approved') {
            throw new RuntimeException('Không thể hủy phiếu đã cân bằng');
        }

        $reconService = new StockReconciliationService($this->repo, $this->validator, $this->ledger);
        $result = $reconService->reject($id, [
            'notes' => $input['notes'] ?? 'Đã hủy',
            'rejected_by' => $input['cancelled_by'] ?? null,
        ]);

        return [
            'success' => true,
            'data' => $this->transformToFrontend($result['data'], true),
        ];
    }

    /**
     * Transform backend reconciliation to frontend audit format.
     * @param array $userMap Pre-fetched user id => name map to avoid N+1 queries
     */
    protected function transformToFrontend(array $row, bool $includeItems = false, array $userMap = []): array
    {
        $items = $row['items'] ?? [];
        
        // Calculate aggregated values
        $actualQuantity = 0;
        $totalActualValue = 0;
        $differenceQuantity = 0;
        $differenceValue = 0;
        $overCountedQty = 0;
        $overCountedValue = 0;
        $underCountedQty = 0;
        $underCountedValue = 0;

        foreach ($items as $item) {
            $countedQty = (float) ($item['counted_qty'] ?? 0);
            $currentQty = (float) ($item['current_qty'] ?? 0);
            $variance = (float) ($item['variance_qty'] ?? ($countedQty - $currentQty));
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            
            $actualQuantity += $countedQty;
            $totalActualValue += $countedQty * $unitCost;
            $differenceQuantity += $variance;
            $differenceValue += $variance * $unitCost;
            
            if ($variance > 0) {
                $overCountedQty += $variance;
                $overCountedValue += $variance * $unitCost;
            } elseif ($variance < 0) {
                $underCountedQty += abs($variance);
                $underCountedValue += abs($variance) * $unitCost;
            }
        }

        // Get user names: prefer userMap (if pre-fetched), fall back to row data
        $createdById = $row['created_by'] ?? null;
        $approvedById = $row['approved_by'] ?? null;
        
        // creatorName: userMap first, then row['creator_name'] fallback
        $creatorName = ($createdById && isset($userMap[$createdById]))
            ? $userMap[$createdById]
            : ($row['creator_name'] ?? null);
        
        // reconciledBy: userMap first, then row['reconciler_name'] fallback
        $reconciledBy = ($approvedById && isset($userMap[$approvedById]))
            ? $userMap[$approvedById]
            : ($row['reconciler_name'] ?? $row['approver_name'] ?? null);

        $result = [
            'id' => $row['id'],
            'code' => $row['recon_number'] ?? ('SK' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)),
            'createdTime' => $row['created_at'] ?? null,
            'creatorName' => $creatorName,
            'reconciledBy' => $reconciledBy,
            'reconciledDate' => $row['approved_at'] ?? null,
            'actualQuantity' => $actualQuantity,
            'totalActualValue' => $totalActualValue,
            'differenceQuantity' => $differenceQuantity,
            'differenceValue' => $differenceValue,
            'overCountedQty' => $overCountedQty,
            'overCountedValue' => $overCountedValue,
            'underCountedQty' => $underCountedQty,
            'underCountedValue' => $underCountedValue,
            'notes' => $row['notes'] ?? '',
            'status' => self::STATUS_MAP[$row['status']] ?? $row['status'],
        ];

        if ($includeItems) {
            $result['items'] = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'productCode' => $item['product_code'] ?? '',
                    'productName' => $item['product_name'] ?? '',
                    'expectedQuantity' => (float) ($item['current_qty'] ?? 0),
                    'actualQuantity' => (float) ($item['counted_qty'] ?? 0),
                    'unitPrice' => (float) ($item['unit_cost'] ?? 0),
                    'difference' => (float) ($item['variance_qty'] ?? 0),
                ];
            }, $items);
        }

        return $result;
    }

    /**
     * Apply date filtering based on frontend dateMode.
     */
    protected function applyDateFilter(array $filters): array
    {
        $mode = $filters['dateMode'] ?? 'this_month';
        $now = new \DateTime();

        switch ($mode) {
            case 'this_month':
                $filters['date_from'] = $now->format('Y-m-01 00:00:00');
                $filters['date_to'] = $now->format('Y-m-t 23:59:59');
                break;
            case 'custom':
                if (!empty($filters['customFrom'])) {
                    $filters['date_from'] = $filters['customFrom'] . ' 00:00:00';
                }
                if (!empty($filters['customTo'])) {
                    $filters['date_to'] = $filters['customTo'] . ' 23:59:59';
                }
                break;
        }

        unset($filters['dateMode'], $filters['customFrom'], $filters['customTo']);
        return $filters;
    }

    /**
     * Apply sorting to data array.
     */
    protected function applySorting(array $data, string $sort): array
    {
        [$field, $direction] = explode(',', $sort) + [null, 'desc'];
        $direction = strtolower($direction) === 'asc' ? 1 : -1;

        usort($data, function ($a, $b) use ($field, $direction) {
            $av = $a[$field] ?? '';
            $bv = $b[$field] ?? '';
            if ($av === $bv) return 0;
            
            // Compare numerically if both values are numeric
            if (is_numeric($av) && is_numeric($bv)) {
                return ((float)$av <=> (float)$bv) * $direction;
            }
            
            return ($av > $bv ? 1 : -1) * $direction;
        });

        return $data;
    }

    /**
     * Calculate summary for list.
     */
    protected function calculateSummary(array $data): array
    {
        return [
            'totalActualQuantity' => array_sum(array_column($data, 'actualQuantity')),
            'totalActualValue' => array_sum(array_column($data, 'totalActualValue')),
            'totalDifferenceQty' => array_sum(array_column($data, 'differenceQuantity')),
            'totalDifferenceValue' => array_sum(array_column($data, 'differenceValue')),
        ];
    }

    /**
     * Batch fetch user names by IDs.
     * Returns an associative array of user_id => name.
     */
    protected function fetchUserNames(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return $this->userRepo->findByIds($userIds);
    }

    /**
     * Generate next audit number.
     */
    protected function nextNumber(int $branchId): string
    {
        return 'SK' . $branchId . '-' . date('YmdHis');
    }

    /**
     * Require that a reconciliation exists.
     */
    protected function require(int $id): array
    {
        $row = $this->repo->find($id);
        if (!$row) {
            throw new RuntimeException('Không tìm thấy phiếu kiểm kho');
        }
        return $row;
    }
}
