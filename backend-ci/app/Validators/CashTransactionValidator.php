<?php

namespace App\Validators;

use App\Models\CashTransactionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate cash transaction inputs (basic field validation only).
 *
 * @agent-validator: Cash transactions basic validation
 * @agent-pattern: Field validation and sanitization
 * @agent-reusable: HIGH
 */
class CashTransactionValidator
{
    protected Validation $validation;
    protected ?BaseConnection $db;

    public function __construct(?Validation $validation = null, ?BaseConnection $db = null)
    {
        $this->validation = $validation ?? Services::validation(null, false);
        $this->db = $db; // Optional for branch validation
    }

    /**
     * Validate receipt data.
     *
     * @agent-use: POST /api/cash/receipt
     * @agent-pattern: Standard receipt validation
     */
    public function validateReceipt(array $data): array
    {
        // Set type to RECEIPT
        $data['type'] = CashTransactionModel::TYPE_RECEIPT;

        // Validate basic fields
        $validated = $this->validateBasicFields($data);

        // Validate category is allowed for RECEIPT
        if (!in_array($validated['category'], CashTransactionModel::getReceiptCategories(), true)) {
            throw new InvalidArgumentException(
                'Invalid category for RECEIPT. Allowed: ' . implode(', ', CashTransactionModel::getReceiptCategories())
            );
        }

        return $validated;
    }

    /**
     * Validate payment data.
     *
     * @agent-use: POST /api/cash/payment
     * @agent-pattern: Standard payment validation
     */
    public function validatePayment(array $data): array
    {
        // Set type to PAYMENT
        $data['type'] = CashTransactionModel::TYPE_PAYMENT;

        // Validate basic fields
        $validated = $this->validateBasicFields($data);

        // Validate category is allowed for PAYMENT
        if (!in_array($validated['category'], CashTransactionModel::getPaymentCategories(), true)) {
            throw new InvalidArgumentException(
                'Invalid category for PAYMENT. Allowed: ' . implode(', ', CashTransactionModel::getPaymentCategories())
            );
        }

        return $validated;
    }


    /**
     * Validate list filters.
     *
     * @agent-use: GET /api/cash/transactions
     * @agent-pattern: Standard list filters
     */
    public function validateListFilters(array $input): array
    {
        $data = array_merge([
            'page' => 1,
            'limit' => 20,
        ], $input);

        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'type' => 'permit_empty|in_list[RECEIPT,PAYMENT]',
            'category' => 'permit_empty|max_length[50]',
            'branch_id' => 'permit_empty|is_natural_no_zero',
            'date_from' => 'permit_empty|valid_date',
            'date_to' => 'permit_empty|valid_date',
            'reference_type' => 'permit_empty|max_length[50]',
            'status' => 'permit_empty|in_list[approved,cancelled,pending]',
            'payment_method' => 'permit_empty|in_list[cash,bank,bank_transfer,ewallet]',
            'staff_name' => 'permit_empty|max_length[120]',
            'payer_name' => 'permit_empty|max_length[180]',
            'payer_phone' => 'permit_empty|max_length[30]',
            'payer_code' => 'permit_empty|max_length[60]',
            'bank_account' => 'permit_empty|max_length[60]',
            'transfer_note' => 'permit_empty|max_length[255]',
        ];

        if (!$this->validation->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(
                implode('; ', array_filter($this->validation->getErrors())) ?: 'Invalid filters'
            );
        }

        $validated = $this->validation->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);

        return $validated;
    }

    /**
     * Validate basic transaction fields.
     */
    protected function validateBasicFields(array $data): array
    {
        // Required fields
        $rules = [
            'type' => 'required|in_list[RECEIPT,PAYMENT]',
            'amount' => 'required|decimal|greater_than[0]',
            'category' => 'required|max_length[50]',
            'payment_method' => 'permit_empty|in_list[cash,bank,bank_transfer,ewallet]',
            'status' => 'permit_empty|in_list[approved,cancelled,pending]',
            'branch_id' => 'required|is_natural_no_zero',
            'created_by' => 'required|is_natural_no_zero',
            'transaction_date' => 'required|valid_date',
        ];

        if (!$this->validation->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(
                implode('; ', array_filter($this->validation->getErrors())) ?: 'Validation failed'
            );
        }

        $validated = $this->validation->getValidated();

        // Validate transaction_date is not in the future
        $transactionDate = strtotime($validated['transaction_date']);
        $today = strtotime(date('Y-m-d'));
        if ($transactionDate > $today) {
            throw new InvalidArgumentException('Transaction date cannot be in the future');
        }

        // Validate branch exists (only if DB connection is available)
        if ($this->db && !$this->branchExists($validated['branch_id'])) {
            throw new InvalidArgumentException('Branch not found or inactive');
        }

        // Add optional fields
        $validated['description'] = isset($data['description']) ? trim((string) $data['description']) : null;
        $validated['note'] = isset($data['note']) ? trim((string) $data['note']) : null;
        $validated['reference_type'] = isset($data['reference_type']) ? trim((string) $data['reference_type']) : null;
        $validated['reference_id'] = isset($data['reference_id']) ? (int) $data['reference_id'] : null;
        $validated['reference_code'] = isset($data['reference_code']) ? trim((string) $data['reference_code']) : null;
        $validated['payment_method'] = isset($data['payment_method']) ? trim((string) $data['payment_method']) : null;
        $validated['status'] = isset($data['status']) ? trim((string) $data['status']) : null;
        $validated['account_name'] = isset($data['account_name']) ? trim((string) $data['account_name']) : null;
        $validated['created_by_name'] = isset($data['created_by_name']) ? trim((string) $data['created_by_name']) : null;
        $validated['staff_name'] = isset($data['staff_name']) ? trim((string) $data['staff_name']) : null;
        $validated['payer_code'] = isset($data['payer_code']) ? trim((string) $data['payer_code']) : null;
        $validated['payer_name'] = isset($data['payer_name']) ? trim((string) $data['payer_name']) : null;
        $validated['payer_phone'] = isset($data['payer_phone']) ? trim((string) $data['payer_phone']) : null;
        $validated['payer_address'] = isset($data['payer_address']) ? trim((string) $data['payer_address']) : null;
        $validated['bank_account'] = isset($data['bank_account']) ? trim((string) $data['bank_account']) : null;
        $validated['transfer_note'] = isset($data['transfer_note']) ? trim((string) $data['transfer_note']) : null;

        return $validated;
    }


    /**
     * Check if branch exists and is active.
     */
    protected function branchExists(int $branchId): bool
    {
        // Use raw SQL to check table existence
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'branches'")->getResultArray();
        if (empty($tableCheck)) {
            return true; // Skip validation if table doesn't exist
        }

        try {
            $branch = $this->db->query("
                SELECT * FROM branches
                WHERE id = ? AND (deleted_at IS NULL OR deleted_at IS NULL)
            ", [$branchId])->getRowArray();
        } catch (\Throwable $e) {
            // If schema differs (e.g., missing status), don't block validation
            return true;
        }

        if (empty($branch)) {
            return false;
        }

        if (isset($branch['status']) && strtolower((string) $branch['status']) !== 'active') {
            return false;
        }

        return true;
    }

}
