<?php

namespace App\Services\PurchaseOrders;

use App\Models\CashTransactionModel;
use App\Services\CashTransactions\CashTransactionService;
use App\Validators\CashTransactionReferenceValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Purchase Order status handling + cash trigger.
 *
 * @agent-service: PurchaseOrder status
 * @agent-pattern: State change side effects
 */
class PurchaseOrderStatusService
{
    protected \CodeIgniter\Database\BaseConnection $db;
    protected CashTransactionService $cash;

    public function __construct(?\CodeIgniter\Database\BaseConnection $db = null, ?CashTransactionService $cash = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->cash = $cash ?? new CashTransactionService(null, null, new CashTransactionReferenceValidator($this->db));
    }

    /**
     * Mark PO as received and auto create cash payment if payment_method = CASH.
     */
    public function markReceived(int $poId, int $userId): array
    {
        $po = $this->db->table('purchase_orders')->where('id', $poId)->get()->getRowArray();
        if (! $po) {
            throw new RuntimeException('Purchase order not found');
        }

        $status = $po['status'] ?? 'draft';
        if ($status !== 'received') {
            $this->db->table('purchase_orders')->where('id', $poId)->update([
                'status' => 'received',
                'received_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $po['status'] = 'received';
        }

        if (($po['payment_method'] ?? 'CASH') !== 'CASH') {
            return ['success' => true, 'data' => $po, 'transaction' => null];
        }

        $branchId = $po['branch_id'] ?? null;
        if (! $branchId) {
            throw new InvalidArgumentException('branch_id is required on purchase order to create payment');
        }

        $payment = $this->cash->createPayment([
            'branch_id' => (int) $branchId,
            'category' => CashTransactionModel::CATEGORY_PURCHASE,
            'amount' => (float) ($po['total'] ?? 0),
            'payment_method' => 'cash',
            'reference_type' => CashTransactionModel::REFERENCE_PURCHASE_ORDER,
            'reference_id' => $poId,
            'reference_code' => $po['po_number'] ?? ($po['code'] ?? null),
            'description' => 'Chi trả PO #' . ($po['po_number'] ?? $poId),
            'transaction_date' => date('Y-m-d'),
            'created_by' => $userId,
        ]);

        return ['success' => true, 'data' => $po, 'transaction' => $payment['data'] ?? null];
    }
}
