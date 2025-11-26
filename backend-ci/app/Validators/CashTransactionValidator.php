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

        $branch = $this->db->query("
            SELECT * FROM branches
            WHERE id = ? AND deleted_at IS NULL AND status = 'active'
        ", [$branchId])->getRowArray();

        return !empty($branch);
    }

}