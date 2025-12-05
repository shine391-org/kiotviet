<?php

namespace App\Repositories\CashTransactions;

use App\Models\CashTransactionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Cash transactions persistence layer.
 *
 * @agent-repository: Cash transactions
 * @agent-pattern: Repository pattern with real-time balance
 * @agent-reusable: HIGH
 */
class CashTransactionRepository
{
    protected CashTransactionModel $model;
    protected BaseConnection $db;

    public function __construct(?CashTransactionModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new CashTransactionModel($this->db);
        
        // DEBUG: Log connection info
        error_log("DEBUG: CashTransactionRepository::__construct() - Connection group: " . (ENVIRONMENT === 'testing' ? 'tests' : 'default'));
        error_log("DEBUG: CashTransactionRepository::__construct() - Database name: " . $this->db->getDatabase());
    }

    /**
     * Create new transaction.
     *
     * @agent-use: Service create methods
     * @agent-pattern: Standard create with timestamps
     */
    public function create(array $data): array
    {
        $payload = $data + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        
        // Ensure transaction_date is a valid date
        if (isset($payload['transaction_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $payload['transaction_date']);
            if ($date) {
                $payload['transaction_date'] = $date->format('Y-m-d');
            }
        }

        // DEBUG: Log the insert operation
        error_log("DEBUG: CashTransactionRepository::create() - About to insert payload: " . json_encode($payload));
        
        try {
            error_log("DEBUG: CashTransactionRepository::create() - About to call model insert");
            
            // Check validation before insert
            if (!$this->model->validate($payload)) {
                $errors = $this->model->errors();
                error_log("DEBUG: CashTransactionRepository::create() - Validation failed before insert: " . json_encode($errors));
                throw new \Exception('Validation failed: ' . implode(', ', $errors));
            }
            
            $this->model->insert($payload);
            $insertId = $this->model->getInsertID();
            
            error_log("DEBUG: CashTransactionRepository::create() - Insert result: " . $insertId);
            error_log("DEBUG: CashTransactionRepository::create() - Last query: " . $this->db->getLastQuery());
            error_log("DEBUG: CashTransactionRepository::create() - DB error: " . json_encode($this->db->error()));
            error_log("DEBUG: CashTransactionRepository::create() - Affected rows: " . $this->db->affectedRows());
            error_log("DEBUG: CashTransactionRepository::create() - Model errors: " . json_encode($this->model->errors()));
            
            // Check if insert actually failed by querying the table
            if ($insertId === 0) {
                error_log("DEBUG: CashTransactionRepository::create() - Insert failed - checking table data");
                $tableData = $this->db->table('cash_transactions')->get()->getResultArray();
                error_log("DEBUG: CashTransactionRepository::create() - Current table data: " . json_encode($tableData));
                
                // Check table structure
                $tableExists = $this->db->tableExists('cash_transactions');
                error_log("DEBUG: CashTransactionRepository::create() - Table exists: " . ($tableExists ? 'true' : 'false'));
                
                if ($tableExists) {
                    $fields = $this->db->getFieldData('cash_transactions');
                    error_log("DEBUG: CashTransactionRepository::create() - Table fields: " . json_encode($fields));
                }
                
                // Try direct SQL insert to see what happens
                error_log("DEBUG: CashTransactionRepository::create() - Trying direct SQL insert");
                $directSql = "INSERT INTO cash_transactions (type, amount, category, branch_id, created_by, transaction_date, description, payment_method, status, account_name, created_at, updated_at) VALUES ('PAYMENT', 200000, 'expense', 1, 1, '2025-11-26', 'Test direct insert', 'cash', 'approved', 'Tiền mặt', NOW(), NOW())";
                $directResult = $this->db->query($directSql);
                error_log("DEBUG: CashTransactionRepository::create() - Direct SQL result: " . ($directResult ? 'success' : 'failed'));
                error_log("DEBUG: CashTransactionRepository::create() - Direct SQL error: " . $this->db->getError());
            }
            
        } catch (\Exception $e) {
            error_log("DEBUG: CashTransactionRepository::create() - Exception: " . $e->getMessage());
            error_log("DEBUG: CashTransactionRepository::create() - Exception trace: " . $e->getTraceAsString());
            throw $e;
        }
        
        // Check if insert actually failed
        if ($insertId === 0) {
            error_log("DEBUG: CashTransactionRepository::create() - Insert failed - checking table structure");
            $tableInfo = $this->db->table('cash_transactions')->get()->getResultArray();
            error_log("DEBUG: CashTransactionRepository::create() - Current table data: " . json_encode($tableInfo));
            
            // Check if table exists and has correct structure
            $tableExists = $this->db->tableExists('cash_transactions');
            error_log("DEBUG: CashTransactionRepository::create() - Table exists: " . ($tableExists ? 'true' : 'false'));
            
            if ($tableExists) {
                $fields = $this->db->getFieldData('cash_transactions');
                error_log("DEBUG: CashTransactionRepository::create() - Table fields: " . json_encode($fields));
            }
        }
        
        // Re-fetch the inserted row to get actual database values
        $insertedRow = $this->model->find($insertId);
        error_log("DEBUG: CashTransactionRepository::create() - Inserted row from DB: " . json_encode($insertedRow));
        
        if (!$insertedRow) {
            error_log("DEBUG: CashTransactionRepository::create() - Failed to re-fetch inserted row");
            $payload['id'] = (int) $insertId;
            return $this->hydrate($payload);
        }

        return $this->hydrate($insertedRow);
    }

    /**
     * Find transaction by ID.
     *
     * @agent-use: Service get method
     * @agent-pattern: Standard find with soft delete check
     */
    public function findById(int $id): ?array
    {
        $row = $this->model->where('deleted_at', null)->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    /**
     * List transactions with filters and pagination.
     *
     * @agent-use: Service list method
     * @agent-pattern: Query builder with filters
     */
    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        $builder = $this->applyFilters($filters);

        $offset = ($page - 1) * $limit;

        $rows = $builder
            ->orderBy('transaction_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        $total = $this->count($filters);

        return [
            'data' => array_map(fn($row) => $this->hydrate($row), $rows),
            'total' => $total
        ];
    }

    /**
     * Count transactions for pagination.
     *
     * @agent-use: Service list method
     */
    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /**
     * Calculate real-time balance.
     *
     * @agent-use: Service getBalance method
     * @agent-pattern: Real-time calculation (no cache)
     * 
     * @param int|null $branchId Filter by branch (null = all branches)
     * @return float Current balance
     */
    public function calculateBalance(?int $branchId = null, ?array $filters = null): float
    {
        $builder = $this->db->table('cash_transactions')
            ->where('deleted_at', null);

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }

        // Apply date filters if provided
        if (!empty($filters['date_from'])) {
            $builder->where('transaction_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $builder->where('transaction_date <=', $filters['date_to']);
        }

        // Calculate RECEIPT total
        $receiptBuilder = clone $builder;
        $query = $receiptBuilder
            ->where('type', CashTransactionModel::TYPE_RECEIPT)
            ->selectSum('amount', 'total')
            ->get();
        $row = $query ? $query->getRow() : null;
        $receiptTotal = (float) ($row->total ?? 0);

        // Calculate PAYMENT total
        $paymentBuilder = clone $builder;
        $query = $paymentBuilder
            ->where('type', CashTransactionModel::TYPE_PAYMENT)
            ->selectSum('amount', 'total')
            ->get();
        $row = $query ? $query->getRow() : null;
        $paymentTotal = (float) ($row->total ?? 0);

        // Balance = RECEIPT - PAYMENT
        return $receiptTotal - $paymentTotal;
    }

    /**
     * Get daily summary for a specific date.
     *
     * @agent-use: Service getDailyReport method
     * @agent-pattern: Grouped aggregation
     *
     * @param string $date Date in Y-m-d format
     * @param int|null $branchId Filter by branch
     * @return array Summary with receipt_total, payment_total, balance
     */
    public function getDailySummary(string $date, ?int $branchId = null): array
    {
        $builder = $this->db->table('cash_transactions')
            ->where('transaction_date', $date)
            ->where('deleted_at', null);

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }

        // Get RECEIPT total
        $receiptBuilder = clone $builder;
        $query = $receiptBuilder
            ->where('type', CashTransactionModel::TYPE_RECEIPT)
            ->selectSum('amount', 'total')
            ->get();
        $row = $query ? $query->getRow() : null;
        $receiptTotal = (float) ($row->total ?? 0);

        // Get PAYMENT total
        $paymentBuilder = clone $builder;
        $query = $paymentBuilder
            ->where('type', CashTransactionModel::TYPE_PAYMENT)
            ->selectSum('amount', 'total')
            ->get();
        $row = $query ? $query->getRow() : null;
        $paymentTotal = (float) ($row->total ?? 0);

        // Get transaction count
        $countBuilder = clone $builder;
        $transactionCount = $countBuilder->countAllResults();

        return [
            'date' => $date,
            'branch_id' => $branchId,
            'receipt_total' => $receiptTotal,
            'payment_total' => $paymentTotal,
            'balance' => $receiptTotal - $paymentTotal, // Changed from 'net' to 'balance'
            'transaction_count' => $transactionCount,
        ];
    }

    /**
     * Soft delete transaction.
     *
     * @agent-use: Service delete method
     * @agent-pattern: Soft delete only
     */
    public function softDelete(int $id): bool
    {
        // Check if transaction exists first
        $transaction = $this->findById($id);
        if (!$transaction) {
            return false;
        }
        
        return (bool) $this->model->delete($id);
    }

    /**
     * Update transaction fields.
     *
     * @agent-use: Service update method (if needed)
     */
    public function update(int $id, array $data): bool
    {
        $payload = $data + ['updated_at' => $this->now()];
        return (bool) $this->model->update($id, $payload);
    }

    /**
     * Apply filters to query builder.
     */
    protected function applyFilters(array $filters)
    {
        $builder = $this->model->builder()->where('deleted_at', null);

        // Filter by type
        if (!empty($filters['type'])) {
            $builder->where('type', $filters['type']);
        }

        // Filter by category
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }

        // Filter by branch
        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', $filters['branch_id']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $builder->where('transaction_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('transaction_date <=', $filters['date_to']);
        }

        // Filter by reference type
        if (!empty($filters['reference_type'])) {
            $builder->where('reference_type', $filters['reference_type']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        // Filter by payment_method
        if (!empty($filters['payment_method'])) {
            $builder->where('payment_method', $filters['payment_method']);
        }

        // Staff / payer filters (LIKE for partial)
        if (!empty($filters['staff_name'])) {
            $builder->like('staff_name', $filters['staff_name']);
        }
        if (!empty($filters['payer_name'])) {
            $builder->like('payer_name', $filters['payer_name']);
        }
        if (!empty($filters['payer_phone'])) {
            $builder->like('payer_phone', $filters['payer_phone']);
        }
        if (!empty($filters['payer_code'])) {
            $builder->like('payer_code', $filters['payer_code']);
        }
        if (!empty($filters['bank_account'])) {
            $builder->like('bank_account', $filters['bank_account']);
        }
        if (!empty($filters['transfer_note'])) {
            $builder->like('transfer_note', $filters['transfer_note']);
        }

        // Search in description or note
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('description', $search)
                ->orLike('note', $search)
                ->orLike('reference_code', $search)
                ->orLike('payer_name', $search)
                ->orLike('payer_code', $search)
                ->groupEnd();
        }

        return $builder;
    }

    /**
     * Hydrate row data (type casting).
     */
    protected function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;
        $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
        $row['status'] = $row['status'] ?? 'approved';
        $row['payment_method'] = $row['payment_method'] ?? 'cash';
        $row['account_name'] = $row['account_name'] ?? ($row['payment_method'] === 'bank' ? 'Ngân hàng' : 'Tiền mặt');
        $row['bank_account'] = $row['bank_account'] ?? null;
        $row['staff_name'] = $row['staff_name'] ?? null;
        $row['payer_code'] = $row['payer_code'] ?? null;
        $row['payer_name'] = $row['payer_name'] ?? null;
        $row['payer_phone'] = $row['payer_phone'] ?? null;
        $row['payer_address'] = $row['payer_address'] ?? null;
        $row['transfer_note'] = $row['transfer_note'] ?? null;

        return $row;
    }

    /**
     * Get current timestamp.
     */
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
