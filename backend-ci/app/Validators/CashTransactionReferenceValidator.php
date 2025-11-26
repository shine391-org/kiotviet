<?php

namespace App\Validators;

use App\Models\CashTransactionModel;
use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

/**
 * Validate cash transaction references with database checks.
 *
 * @agent-validator: Cash transaction references
 * @agent-pattern: Database-dependent validation
 * @agent-reusable: HIGH
 */
class CashTransactionReferenceValidator
{
    protected BaseConnection $db;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * Validate reference exists and amount matches.
     *
     * @agent-use: Service layer before creating transaction
     * @agent-pattern: Reference validation with amount matching
     * 
     * @param string $type Reference type (order, purchase_order, etc.)
     * @param int $id Reference ID
     * @param float $amount Transaction amount to validate
     * @return array Reference data
     * @throws InvalidArgumentException
     */
    public function validateReference(string $type, int $id, float $amount): array
    {
        if ($type === CashTransactionModel::REFERENCE_MANUAL) {
            return ['valid' => true];
        }

        if ($type === CashTransactionModel::REFERENCE_ORDER) {
            return $this->validateOrderReference($id, $amount);
        }

        if ($type === CashTransactionModel::REFERENCE_PURCHASE_ORDER) {
            return $this->validatePurchaseOrderReference($id, $amount);
        }

        throw new InvalidArgumentException("Invalid reference type: {$type}");
    }

    /**
     * Validate order reference and amount.
     */
    protected function validateOrderReference(int $orderId, float $amount): array
    {
        // Use raw SQL to check table existence
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'orders'")->getResultArray();
        if (empty($tableCheck)) {
            throw new InvalidArgumentException('Orders table not found');
        }

        $order = $this->db->query("
            SELECT * FROM orders
            WHERE id = ? AND deleted_at IS NULL
        ", [$orderId])->getRowArray();

        if (!$order) {
            throw new InvalidArgumentException("Order #{$orderId} not found");
        }

        // Validate amount matches order total
        $orderTotal = (float) $order['total'];
        if (abs($orderTotal - $amount) > 0.01) { // Allow 0.01 difference for floating point
            throw new InvalidArgumentException("Amount does not match order total");
        }

        // Check for duplicate payment
        if ($this->hasExistingPayment('order', $orderId)) {
            throw new InvalidArgumentException("Order #{$orderId} already has a payment transaction");
        }

        return [
            'valid' => true,
            'order_id' => $orderId,
            'order_number' => $order['order_number'] ?? null,
            'total' => $orderTotal,
        ];
    }

    /**
     * Validate purchase order reference and amount.
     */
    protected function validatePurchaseOrderReference(int $poId, float $amount): array
    {
        // Use raw SQL to check table existence
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'purchase_orders'")->getResultArray();
        if (empty($tableCheck)) {
            throw new InvalidArgumentException('Purchase orders table not found');
        }

        $po = $this->db->query("
            SELECT * FROM purchase_orders
            WHERE id = ? AND deleted_at IS NULL
        ", [$poId])->getRowArray();

        if (!$po) {
            throw new InvalidArgumentException("Purchase order #{$poId} not found");
        }

        // Validate amount matches PO total
        $poTotal = (float) $po['total'];
        if (abs($poTotal - $amount) > 0.01) {
            throw new InvalidArgumentException("Amount does not match purchase order total");
        }

        // Check for duplicate payment
        if ($this->hasExistingPayment('purchase_order', $poId)) {
            throw new InvalidArgumentException("Purchase order already paid");
        }

        return [
            'valid' => true,
            'purchase_order_id' => $poId,
            'po_number' => $po['po_number'] ?? null,
            'total' => $poTotal,
        ];
    }

    /**
     * Check if reference already has a payment transaction.
     */
    protected function hasExistingPayment(string $referenceType, int $referenceId): bool
    {
        // Use raw SQL to check table existence
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'cash_transactions'")->getResultArray();
        if (empty($tableCheck)) {
            return false;
        }

        $existing = $this->db->query("
            SELECT * FROM cash_transactions
            WHERE reference_type = ? AND reference_id = ? AND deleted_at IS NULL
        ", [$referenceType, $referenceId])->getRowArray();

        return !empty($existing);
    }
}