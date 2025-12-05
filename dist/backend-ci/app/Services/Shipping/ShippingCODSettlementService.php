<?php

namespace App\Services\Shipping;

use App\Repositories\Orders\OrderRepository;
use App\Services\CashTransactions\CashTransactionService;
use App\Validators\CashTransactionReferenceValidator;
use App\Models\CashTransactionModel;
use InvalidArgumentException;

/**
 * Đối soát COD với đơn vị vận chuyển.
 *
 * @agent-service: Shipping COD settlement
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ShippingCODSettlementService
{
    protected OrderRepository $orders;
    protected CashTransactionService $cash;

    public function __construct(?OrderRepository $orders = null, ?CashTransactionService $cash = null)
    {
        $db = \Config\Database::connect();
        $this->orders = $orders ?? new OrderRepository(null, null, $db);
        $this->cash = $cash ?? new CashTransactionService(null, null, new CashTransactionReferenceValidator($db));
    }

    /**
     * Đối soát COD.
     *
     * @param int   $shippingPartnerId
     * @param array $orderIds Danh sách order đã giao thành công
     * @param float $totalShippingFee Tổng phí ship phải trả
     * @param int   $userId Người thực hiện
     * @param int|null $branchId Override chi nhánh (nếu null lấy theo order đầu tiên)
     */
    public function settleCOD(int $shippingPartnerId, array $orderIds, float $totalShippingFee, int $userId, ?int $branchId = null): array
    {
        if ($shippingPartnerId <= 0) {
            throw new InvalidArgumentException('shipping_partner_id invalid');
        }
        if (empty($orderIds)) {
            throw new InvalidArgumentException('order_ids is required');
        }

        $totalCOD = 0;
        $branch = $branchId;
        foreach ($orderIds as $id) {
            $order = $this->orders->findById((int) $id);
            if (! $order) {
                throw new InvalidArgumentException("Order #{$id} not found");
            }
            if (($order['payment_method'] ?? null) !== 'COD') {
                continue; // only COD orders
            }
            if (($order['status'] ?? null) !== 'delivered' && ($order['status'] ?? null) !== 'completed') {
                continue; // only delivered/completed
            }
            $totalCOD += (float) ($order['total'] ?? 0);
            $branch = $branch ?? ($order['branch_id'] ?? null);
        }

        if (! $branch) {
            throw new InvalidArgumentException('branch_id missing for settlement');
        }

        $netAmount = $totalCOD - $totalShippingFee;
        $createdTransactions = [];

        if ($netAmount > 0) {
            $createdTransactions[] = $this->cash->createReceipt([
                'branch_id' => (int) $branch,
                'category' => CashTransactionModel::CATEGORY_SHIPPING_COD,
                'amount' => $netAmount,
                'payment_method' => 'bank_transfer',
                'reference_type' => CashTransactionModel::REFERENCE_SHIPPING_SETTLEMENT,
                'reference_id' => $shippingPartnerId,
                'description' => 'Thu COD đối soát ' . count($orderIds) . ' đơn',
                'transaction_date' => date('Y-m-d'),
                'created_by' => $userId,
            ])['data'];
        } elseif ($netAmount < 0) {
            $createdTransactions[] = $this->cash->createPayment([
                'branch_id' => (int) $branch,
                'category' => CashTransactionModel::CATEGORY_SHIPPING_FEE,
                'amount' => abs($netAmount),
                'payment_method' => 'bank_transfer',
                'reference_type' => CashTransactionModel::REFERENCE_SHIPPING_SETTLEMENT,
                'reference_id' => $shippingPartnerId,
                'description' => 'Trả phí ship đối soát ' . count($orderIds) . ' đơn',
                'transaction_date' => date('Y-m-d'),
                'created_by' => $userId,
            ])['data'];
        }

        // Update paid_amount/debt/payment_status for COD orders in list
        foreach ($orderIds as $id) {
            $order = $this->orders->findById((int) $id);
            if (! $order) { continue; }
            if (($order['payment_method'] ?? null) !== 'COD') { continue; }
            $total = (float) ($order['total'] ?? 0);
            $this->orders->updateFields((int) $id, [
                'paid_amount' => $total,
                'debt_amount' => 0,
                'payment_status' => 'paid',
            ]);
        }

        return [
            'success' => true,
            'data' => [
                'total_cod' => $totalCOD,
                'total_shipping_fee' => $totalShippingFee,
                'net_amount' => $netAmount,
                'orders_count' => count($orderIds),
                'transactions' => $createdTransactions,
            ],
        ];
    }
}
