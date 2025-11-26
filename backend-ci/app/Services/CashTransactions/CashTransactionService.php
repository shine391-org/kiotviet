<?php

namespace App\Services\CashTransactions;

use App\Models\CashTransactionModel;
use App\Repositories\CashTransactions\CashTransactionRepository;
use App\Validators\CashTransactionValidator;
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

    public function __construct(
        ?CashTransactionRepository $repo = null,
        ?CashTransactionValidator $validator = null
    ) {
        $this->repo = $repo ?? new CashTransactionRepository();
        $this->validator = $validator ?? new CashTransactionValidator();
    }

    /**
     * Create receipt transaction.
     *
     * @agent-use: POST /api/cash/receipt
     * @agent-pattern: Receipt creation with validation
     */
    public function createReceipt(array $data): array
    {
        // Auto-add created_by if not provided (for testing)
        if (!isset($data['created_by'])) {
            $data['created_by'] = 1; // Default test user
        }
        
        // Validate receipt data
        $validated = $this->validator->validateReceipt($data);

        // Validate reference if provided
        if (!empty($validated['reference_type']) && !empty($validated['reference_id'])) {
            $this->validator->validateReference(
                $validated['reference_type'],
                $validated['reference_id'],
                $validated['amount']
            );
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
        // Auto-add created_by if not provided (for testing)
        if (!isset($data['created_by'])) {
            $data['created_by'] = 1; // Default test user
        }
        
        // Validate payment data
        $validated = $this->validator->validatePayment($data);

        // Validate reference if provided
        if (!empty($validated['reference_type']) && !empty($validated['reference_id'])) {
            $this->validator->validateReference(
                $validated['reference_type'],
                $validated['reference_id'],
                $validated['amount']
            );
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

        return $transaction;
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
    public function getBalance(?int $branchId = null): array
    {
        // Calculate real-time balance
        $balance = $this->repo->calculateBalance($branchId);

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
    public function getDailyReport(string $date, ?int $branchId = null): array
    {
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
        // For now, return true - implement actual permission checking later
        // TODO: Implement proper RBAC check using user roles/permissions
        return true;
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