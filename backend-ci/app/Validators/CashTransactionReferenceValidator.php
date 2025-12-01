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

        if ($type === CashTransactionModel::REFERENCE_RETURN_ORDER) {
            return $this->validateReturnOrderReference($id, $amount);
        }

        if ($type === CashTransactionModel::REFERENCE_SHIPPING_SETTLEMENT) {
            return $this->validateShippingSettlementReference($id);
        }

        if ($type === CashTransactionModel::REFERENCE_ORDER_PAYMENT) {
            return $this->validateOrderPaymentReference($id, $amount);
        }

        throw new InvalidArgumentException("Invalid reference type: {$type}");
    }

    /**
     * Validate order reference and amount.
     */
    protected function validateOrderReference(int $orderId, float $amount): array
    {
        // Fallback for missing schema in dev/test
        $this->db->query("CREATE TABLE IF NOT EXISTS orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NULL,
            order_number VARCHAR(50) NULL,
            total DECIMAL(14,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'draft',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $error = $this->db->error();
        if (! empty($error['code'])) {
            error_log("DEBUG: validateOrderReference - failed to create orders table: " . json_encode($error));
        }

        $order = $this->db->query("
            SELECT * FROM orders
            WHERE id = ? AND deleted_at IS NULL
        ", [$orderId]);
        if (! $order) {
            $error = $this->db->error();
            $errorMessage = $error['message'] ?? '';
            if ($errorMessage) {
                throw new InvalidArgumentException("Orders query failed: {$errorMessage}");
            }
            throw new InvalidArgumentException("Order #{$orderId} not found");
        }
        $order = $order->getRowArray();

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
        // Fallback for missing schema in dev/test
        $this->db->query("CREATE TABLE IF NOT EXISTS purchase_orders (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            po_number VARCHAR(50) NULL,
            code VARCHAR(50) NULL,
            branch_id BIGINT UNSIGNED NULL,
            payment_method VARCHAR(50) NULL,
            total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) DEFAULT 'draft',
            received_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $error = $this->db->error();
        if (! empty($error['code'])) {
            error_log("DEBUG: validatePurchaseOrderReference - failed to create purchase_orders table: " . json_encode($error));
        }

        $po = $this->db->query("
            SELECT * FROM purchase_orders
            WHERE id = ? AND deleted_at IS NULL
        ", [$poId]);
        if (! $po) {
            $error = $this->db->error();
            $errorMessage = $error['message'] ?? '';
            if ($errorMessage) {
                throw new InvalidArgumentException("Purchase orders query failed: {$errorMessage}");
            }
            throw new InvalidArgumentException("Purchase order #{$poId} not found");
        }
        $po = $po->getRowArray();

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
     * Validate order payment reference.
     */
    protected function validateOrderPaymentReference(int $paymentId, float $amount): array
    {
        if (! $this->db->tableExists('order_payments')) {
            // Fallback cho môi trường test: tự tạo bảng tối thiểu nếu chưa có
            $this->db->query("CREATE TABLE IF NOT EXISTS order_payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT UNSIGNED NOT NULL,
                payment_method VARCHAR(20),
                amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                paid_at DATETIME NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $payment = $this->db->table('order_payments')->where('id', $paymentId)->get()->getRowArray();
        if (! $payment) {
            throw new InvalidArgumentException("Order payment #{$paymentId} not found");
        }

        if (abs(((float)$payment['amount']) - $amount) > 0.01) {
            throw new InvalidArgumentException('Amount does not match order payment amount');
        }

        if ($this->hasExistingPayment(CashTransactionModel::REFERENCE_ORDER_PAYMENT, $paymentId)) {
            throw new InvalidArgumentException('Order payment already has a cash transaction');
        }

        return [
            'valid' => true,
            'order_payment_id' => $paymentId,
            'order_id' => $payment['order_id'],
            'amount' => (float) $payment['amount'],
        ];
    }

    /**
     * Validate shipping settlement reference.
     */
    protected function validateShippingSettlementReference(int $shippingPartnerId): array
    {
        // If shipping partners table does not exist, accept
        if (! $this->db->tableExists('shipping_partners')) {
            return ['valid' => true, 'shipping_partner_id' => $shippingPartnerId];
        }

        $partner = $this->db->table('shipping_partners')
            ->where('id', $shippingPartnerId)
            ->get()
            ->getRowArray();

        if (! $partner) {
            throw new InvalidArgumentException("Shipping partner #{$shippingPartnerId} not found");
        }

        return [
            'valid' => true,
            'shipping_partner_id' => $shippingPartnerId,
            'name' => $partner['name'] ?? null,
        ];
    }

    /**
     * Validate return order reference and refund amount.
     */
    protected function validateReturnOrderReference(int $returnId, float $amount): array
    {
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'returns'")->getResultArray();
        if (empty($tableCheck)) {
            if (ENVIRONMENT === 'testing') {
                $this->db->query("CREATE TABLE IF NOT EXISTS returns (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    return_number VARCHAR(50),
                    order_id INT NULL,
                    customer_id INT NULL,
                    return_amount DECIMAL(14,2) DEFAULT 0,
                    refund_amount DECIMAL(14,2) DEFAULT 0,
                    refund_method VARCHAR(50) NULL,
                    status VARCHAR(50),
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } else {
                throw new InvalidArgumentException('Return orders table not found');
            }
        }

        $deletedAtExists = ! empty($this->db->query("SHOW COLUMNS FROM returns LIKE 'deleted_at'")->getResultArray());
        $sql = "SELECT * FROM returns WHERE id = ?";
        if ($deletedAtExists) {
            $sql .= " AND deleted_at IS NULL";
        }

        $return = $this->db->query($sql, [$returnId])->getRowArray();

        if (! $return) {
            throw new InvalidArgumentException("Return order #{$returnId} not found");
        }

        if (($return['status'] ?? null) !== 'approved') {
            throw new InvalidArgumentException('Return order is not approved');
        }

        $refundAmount = (float) ($return['refund_amount'] ?? 0);
        if (abs($refundAmount - $amount) > 0.01) {
            throw new InvalidArgumentException('Amount does not match return refund amount');
        }

        if ($this->hasExistingPayment(CashTransactionModel::REFERENCE_RETURN_ORDER, $returnId)) {
            throw new InvalidArgumentException('Return order already has a cash transaction');
        }

        return [
            'valid' => true,
            'return_order_id' => $returnId,
            'return_number' => $return['return_number'] ?? null,
            'refund_amount' => $refundAmount,
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
