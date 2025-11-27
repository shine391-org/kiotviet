<?php

namespace App\Services\CashTransactions;

use App\Models\CashTransactionModel;
use App\Repositories\CashTransactions\CashTransactionRepository;
use App\Validators\CashTransactionValidator;
use App\Validators\CashTransactionReferenceValidator;
use App\Models\ModelHasRolesModel;
use App\Models\PermissionModel;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;
use RuntimeException;

/**
 * Business logic for cash transactions.
 *
 * @agent-service: Cash transactions
 * @agent-pattern: Service orchestrator with permission checks
 * @agent-reusable: HIGH
 */
class CashTransactionService
{
    protected CashTransactionRepository $repo;
    protected CashTransactionValidator $validator;
    protected ?CashTransactionReferenceValidator $referenceValidator;

    public function __construct(
        ?CashTransactionRepository $repo = null,
        ?CashTransactionValidator $validator = null,
        ?CashTransactionReferenceValidator $referenceValidator = null
    ) {
        $this->repo = $repo ?? new CashTransactionRepository();
        $this->validator = $validator ?? new CashTransactionValidator();
        $this->referenceValidator = $referenceValidator ?? new CashTransactionReferenceValidator(\Config\Database::connect());
    }

    /**
     * Create receipt transaction.
     *
     * @agent-use: POST /api/cash/receipt
     * @agent-pattern: Receipt creation with validation
     */
    public function createReceipt(array $data): array
    {
        // Validate created_by is provided
        if (!isset($data['created_by'])) {
            throw new InvalidArgumentException('created_by field is required');
        }
        
        // Validate receipt data
        $validated = $this->validator->validateReceipt($data);
        $validated['payment_method'] = $validated['payment_method'] ?? 'cash';
        $validated['status'] = $validated['status'] ?? 'approved';
        $validated['account_name'] = $validated['account_name'] ?? ($validated['payment_method'] === 'bank' ? 'Ngân hàng' : 'Tiền mặt');

        // Validate reference if provided
        if (!empty($validated['reference_type']) && !empty($validated['reference_id'])) {
            if ($this->referenceValidator) {
                $this->referenceValidator->validateReference(
                    $validated['reference_type'],
                    $validated['reference_id'],
                    $validated['amount']
                );
            }
        }

        // Create transaction
        $transaction = $this->repo->create($validated);

        return [
            'success' => true,
            'message' => 'Receipt created successfully',
            'data' => $transaction,
        ];
    }

    /**
     * Create payment transaction.
     *
     * @agent-use: POST /api/cash/payment
     * @agent-pattern: Payment creation with validation
     */
    public function createPayment(array $data): array
    {
        // Validate created_by is provided
        if (!isset($data['created_by'])) {
            throw new InvalidArgumentException('created_by field is required');
        }
        
        // Validate payment data
        $validated = $this->validator->validatePayment($data);
        $validated['payment_method'] = $validated['payment_method'] ?? 'cash';
        $validated['status'] = $validated['status'] ?? 'approved';
        $validated['account_name'] = $validated['account_name'] ?? ($validated['payment_method'] === 'bank' ? 'Ngân hàng' : 'Tiền mặt');

        // Validate reference if provided
        if (!empty($validated['reference_type']) && !empty($validated['reference_id'])) {
            if ($this->referenceValidator) {
                $this->referenceValidator->validateReference(
                    $validated['reference_type'],
                    $validated['reference_id'],
                    $validated['amount']
                );
            }
        }

        // Create transaction
        $transaction = $this->repo->create($validated);

        return [
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $transaction,
        ];
    }

    /**
     * Get transaction by ID.
     *
     * @agent-use: GET /api/cash/transactions/{id}
     * @agent-pattern: Standard get with error handling
     */
    public function getTransaction(int $id): array
    {
        $transaction = $this->repo->findById($id);
        if (!$transaction) {
            throw new InvalidArgumentException('Transaction not found');
        }

        return [
            'success' => true,
            'data' => $transaction,
        ];
    }

    /**
     * List transactions with filters.
     *
     * @agent-use: GET /api/cash/transactions
     * @agent-pattern: Standard list with pagination
     */
    public function listTransactions(array $filters, int $page = 1, int $limit = 20): array
    {
        // Validate filters
        $validated = $this->validator->validateListFilters($filters);
        $validated['page'] = $page;
        $validated['limit'] = $limit;

        // Get data
        $result = $this->repo->list($validated);

        // Post-filter for status/payment_method if provided (in case DB lacks indexes)
        if (!empty($validated['status'])) {
            $result['data'] = array_values(array_filter($result['data'], fn($r) => ($r['status'] ?? null) === $validated['status']));
        }
        if (!empty($validated['payment_method'])) {
            $result['data'] = array_values(array_filter($result['data'], fn($r) => ($r['payment_method'] ?? null) === $validated['payment_method']));
        }

        return [
            'success' => true,
            'data' => $result['data'],
            'pagination' => $this->formatPagination($validated, $result['total']),
        ];
    }

    /**
     * Get current balance (real-time).
     *
     * @agent-use: GET /api/cash/balance
     * @agent-pattern: Real-time balance calculation
     */
    public function getBalance(?int $branchId = null, array $filters = []): array
    {
        // Calculate real-time balance with filters (date range)
        $balance = $this->repo->calculateBalance($branchId, $filters);

        return [
            'success' => true,
            'data' => [
                'branch_id' => $branchId,
                'balance' => $balance,
                'as_of' => date('Y-m-d H:i:s'),
                'currency' => 'VND',
            ],
        ];
    }

    /**
     * Get daily report.
     *
     * @agent-use: GET /api/cash/report/daily
     * @agent-pattern: Daily summary with net calculation
     */
    public function getDailyReport(?string $date, ?int $branchId = null): array
    {
        // Validate date parameter
        if (empty($date)) {
            throw new InvalidArgumentException('Date parameter is required');
        }
        
        // Validate date format
        if (!strtotime($date)) {
            throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
        }

        // Get daily summary
        $summary = $this->repo->getDailySummary($date, $branchId);
        
        // Add net field (same as balance for compatibility)
        $summary['net'] = $summary['balance'];

        return [
            'success' => true,
            'data' => $summary,
        ];
    }

    /**
     * Delete transaction (soft delete).
     *
     * @agent-use: DELETE /api/cash/transactions/{id}
     * @agent-pattern: Soft delete with age validation
     */
    public function deleteTransaction(int $id): array
    {
        // Get transaction
        $transaction = $this->repo->findById($id);
        if (!$transaction) {
            throw new InvalidArgumentException('Transaction not found');
        }

        // Validate transaction age (cannot delete if older than 30 days)
        $transactionDate = strtotime($transaction['transaction_date']);
        $thirtyDaysAgo = strtotime('-30 days');
        if ($transactionDate < $thirtyDaysAgo) {
            throw new InvalidArgumentException(
                'Cannot delete transactions older than 30 days. Transaction date: ' . $transaction['transaction_date']
            );
        }

        // Soft delete
        $success = $this->repo->softDelete($id);
        if (!$success) {
            throw new InvalidArgumentException('Failed to delete transaction');
        }

        return [
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ];
    }

    /**
     * Check if user has permission for cash operations.
     *
     * @agent-use: Service layer permission checking
     * @agent-pattern: Permission validation
     */
    public function checkPermission(int $userId, string $permission): bool
    {
        // Basic RBAC using model_has_roles and role_has_permissions
        $userRole = (new ModelHasRolesModel())
            ->where('model_id', $userId)
            ->first();

        if (!$userRole) {
            return false;
        }

        $perm = (new PermissionModel())
            ->select('permissions.id')
            ->join('role_has_permissions rp', 'rp.permission_id = permissions.id')
            ->where('rp.role_id', $userRole['role_id'])
            ->where('permissions.name', $permission)
            ->where('permissions.deleted_at', null)
            ->first();

        return (bool) $perm;
    }

    /**
     * Validate user can create cash transaction.
     */
    protected function validateCreatePermission(int $userId): void
    {
        if (!$this->checkPermission($userId, 'cash.create')) {
            throw new RuntimeException('You do not have permission to create cash transactions');
        }
    }

    /**
     * Validate user can delete cash transaction.
     */
    protected function validateDeletePermission(int $userId): void
    {
        if (!$this->checkPermission($userId, 'cash.delete')) {
            throw new RuntimeException('You do not have permission to delete cash transactions');
        }
    }

    /**
     * Validate user can view cash transactions.
     */
    protected function validateViewPermission(int $userId): void
    {
        if (!$this->checkPermission($userId, 'cash.view')) {
            throw new RuntimeException('You do not have permission to view cash transactions');
        }
    }

    /**
     * Format pagination response.
     */
    protected function formatPagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Get transaction type display name.
     */
    public static function getTypeDisplayName(string $type): string
    {
        return match($type) {
            CashTransactionModel::TYPE_RECEIPT => 'Thu',
            CashTransactionModel::TYPE_PAYMENT => 'Chi',
            default => $type,
        };
    }

    /**
     * Get category display name.
     */
    public static function getCategoryDisplayName(string $category): string
    {
        $categories = [
            // RECEIPT categories
            CashTransactionModel::CATEGORY_SALES => 'Bán hàng',
            CashTransactionModel::CATEGORY_REFUND => 'Hoàn tiền',
            CashTransactionModel::CATEGORY_DEPOSIT => 'Tiền cọc',
            CashTransactionModel::CATEGORY_OTHER_INCOME => 'Thu nhập khác',
            
            // PAYMENT categories
            CashTransactionModel::CATEGORY_PURCHASE => 'Mua hàng',
            CashTransactionModel::CATEGORY_SALARY => 'Lương',
            CashTransactionModel::CATEGORY_EXPENSE => 'Chi phí vận hành',
            CashTransactionModel::CATEGORY_WITHDRAWAL => 'Rút tiền',
            CashTransactionModel::CATEGORY_OTHER_EXPENSE => 'Chi phí khác',
        ];

        return $categories[$category] ?? $category;
    }
}
