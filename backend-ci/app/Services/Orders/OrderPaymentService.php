<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderPaymentRepository;
use App\Repositories\Orders\OrderRepository;
use App\Validators\OrderPaymentValidator;
use App\Models\CashTransactionModel;
use App\Services\CashTransactions\CashTransactionService;
use App\Validators\CashTransactionReferenceValidator;
use InvalidArgumentException;

class OrderPaymentService
{
    protected OrderPaymentRepository $payments;
    protected OrderRepository $orders;
    protected OrderPaymentValidator $validator;
    protected CashTransactionService $cash;

    public function __construct(
        ?OrderPaymentRepository $payments = null,
        ?OrderRepository $orders = null,
        ?OrderPaymentValidator $validator = null,
        ?CashTransactionService $cash = null,
        ?\CodeIgniter\Database\BaseConnection $db = null
    ) {
        $db = $db ?? \Config\Database::connect();
        $this->payments = $payments ?? new OrderPaymentRepository($db);
        $this->orders = $orders ?? new OrderRepository(null, null, $db);
        $this->validator = $validator ?? new OrderPaymentValidator();
        if ($cash) {
            $this->cash = $cash;
        } else {
            $cashRepo = new \App\Repositories\CashTransactions\CashTransactionRepository(null, $db);
            $cashValidator = new \App\Validators\CashTransactionValidator(null, $db);
            $cashRefValidator = new CashTransactionReferenceValidator($db);
            $this->cash = new CashTransactionService($cashRepo, $cashValidator, $cashRefValidator);
        }
    }

    public function addPayment(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        $order = $this->orders->findById($validated['order_id']);
        if (! $order) {
            throw new InvalidArgumentException('Order not found');
        }

        $newPaid = (float) ($order['paid_amount'] ?? 0) + $validated['amount'];
        if ($newPaid > (float) ($order['total'] ?? 0) + 0.0001) {
            throw new InvalidArgumentException('Overpaid');
        }

        $paymentId = $this->payments->create($validated);

        $this->orders->updateFields($validated['order_id'], [
            'paid_amount' => $newPaid,
            'debt_amount' => max(0, (float) ($order['total'] ?? 0) - $newPaid),
            'payment_status' => $this->paymentStatus((float) ($order['total'] ?? 0), $newPaid),
            'is_paid' => $this->isPaid((float) ($order['total'] ?? 0), $newPaid),
        ]);

        // Auto-create receipt for all payment methods
        $this->cash->createReceipt([
            'branch_id' => (int) ($order['branch_id'] ?? 1),
            'category' => CashTransactionModel::CATEGORY_SALES,
            'amount' => $validated['amount'],
            'payment_method' => $this->mapPaymentMethod($validated['payment_method']),
            'reference_type' => CashTransactionModel::REFERENCE_ORDER_PAYMENT,
            'reference_id' => $paymentId,
            'description' => 'Thu tiền đơn #' . ($order['order_number'] ?? $order['id']) . ' - ' . $this->getPaymentMethodLabel($validated['payment_method']),
            'transaction_date' => date('Y-m-d'),
            'created_by' => $data['created_by'] ?? 1,
        ]);

        $paymentRow = $validated + ['id' => $paymentId];
        return ['success' => true, 'data' => $paymentRow];
    }

    /**
     * Map order payment method to cash transaction payment method.
     */
    private function mapPaymentMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'CASH' => 'cash',
            'BANK_TRANSFER', 'BANK' => 'bank',
            'CARD', 'CREDIT_CARD', 'DEBIT_CARD' => 'card',
            'EWALLET', 'MOMO', 'ZALOPAY', 'VNPAY' => 'ewallet',
            'COD' => 'cod',
            default => 'cash',
        };
    }

    /**
     * Get human-readable label for payment method.
     */
    private function getPaymentMethodLabel(string $method): string
    {
        return match (strtoupper($method)) {
            'CASH' => 'tiền mặt',
            'BANK_TRANSFER', 'BANK' => 'chuyển khoản',
            'CARD', 'CREDIT_CARD', 'DEBIT_CARD' => 'thẻ',
            'EWALLET', 'MOMO', 'ZALOPAY', 'VNPAY' => 'ví điện tử',
            'COD' => 'thu hộ COD',
            default => $method,
        };
    }

    private function paymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0) return 'unpaid';
        if ($paid + 0.0001 >= $total) return 'paid';
        return 'partial';
    }

    private function isPaid(float $total, float $paid): int
    {
        return ($paid + 0.0001 >= $total) ? 1 : 0;
    }
}
