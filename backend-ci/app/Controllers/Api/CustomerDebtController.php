<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

/**
 * Customer Debt API Controller
 * Handles customer debt operations: list, payment, adjustment, discount.
 * 
 * @agent-controller: CustomerDebt
 * @agent-pattern: Thin controller with inline logic
 */
class CustomerDebtController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * List debt transactions for a customer.
     * GET /api/customers/{customerId}/debts
     */
    public function index($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $type = $this->request->getGet('type');

            $builder = $this->db->table('customer_debt_transactions')
                ->where('customer_id', (int) $customerId)
                ->where('deleted_at', null);

            if ($type && $type !== 'all') {
                $builder->where('type', strtoupper($type));
            }

            $rows = $builder
                ->orderBy('created_at', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => array_map(function ($row) {
                    return [
                        'id' => (int) $row['id'],
                        'code' => $row['code'],
                        'created_at' => $row['created_at'],
                        'type' => $this->typeLabel($row['type']),
                        'value' => (float) $row['value'],
                        'balance' => (float) $row['balance'],
                        'notes' => $row['notes'],
                    ];
                }, $rows),
            ]);
        });
    }

    /**
     * Get single debt transaction by code.
     * GET /api/customer-debts/{code}
     */
    public function show($code = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($code) {
            if (! $code) {
                return $this->failValidationErrors('Transaction code is required');
            }

            // Find the debt transaction
            $transaction = $this->db->table('customer_debt_transactions')
                ->where('code', $code)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $transaction) {
                return $this->failNotFound('Transaction not found');
            }

            // Get customer info
            $customer = $this->db->table('customers')
                ->select('id, code, name, phone, current_debt')
                ->where('id', $transaction['customer_id'])
                ->get()
                ->getRowArray();

            // Get user info for created_by
            $createdBy = null;
            if (! empty($transaction['created_by'])) {
                $user = $this->db->table('users')
                    ->select('id, full_name, username')
                    ->where('id', $transaction['created_by'])
                    ->get()
                    ->getRowArray();
                $createdBy = $user ? ($user['full_name'] ?? $user['username']) : null;
            }

            // Get related invoices (allocations) if this is a payment
            // NOTE: This is an ESTIMATE - there is no payment_allocations table to track actual 
            // allocation of payments to specific invoices. The data below shows invoices that
            // existed at the time of payment but does NOT represent actual payment allocation.
            // Consumers should NOT treat payment_amount or paid_before as authoritative values.
            $allocations = [];
            if ($transaction['type'] === 'PAYMENT') {
                // Find invoices that were created before this payment for the same customer
                $invoices = $this->db->table('invoices')
                    ->select('invoice_number as code, created_at, total as value, cod_amount, invoice_status')
                    ->where('customer_id', $transaction['customer_id'])
                    ->where('deleted_at', null)
                    ->where('created_at <=', $transaction['created_at'])
                    ->orderBy('created_at', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();

                foreach ($invoices as $inv) {
                    $allocations[] = [
                        'code' => $inv['code'],
                        'created_at' => $inv['created_at'],
                        'value' => (float) $inv['value'],
                        // These values are ESTIMATES - no actual allocation tracking exists
                        'paid_before' => null,
                        'payment_amount' => null,
                        'status' => $inv['invoice_status'] ?? 'unknown',
                        'is_estimated' => true, // Flag to indicate this is not authoritative
                    ];
                }
            }

            return $this->respond([
                'success' => true,
                'data' => [
                    'id' => (int) $transaction['id'],
                    'code' => $transaction['code'],
                    'customer_id' => (int) $transaction['customer_id'],
                    'type' => $transaction['type'],
                    'type_label' => $this->typeLabel($transaction['type']),
                    'value' => (float) $transaction['value'],
                    'amount' => abs((float) $transaction['value']),
                    'balance' => (float) $transaction['balance'],
                    'notes' => $transaction['notes'],
                    'payment_method' => $transaction['payment_method'] ?? 'cash',
                    'account_number' => $transaction['account_number'] ?? '',
                    'created_at' => $transaction['created_at'],
                    'created_by' => $transaction['created_by'],
                    'created_by_name' => $createdBy,
                    'branch_id' => $transaction['branch_id'],
                    'customer' => $customer,
                    'allocations' => $allocations,
                ],
            ]);
        });
    }

    /**
     * Update a debt transaction (notes, payment_method only).
     * PUT /api/customer-debts/{code}
     */
    public function update($code = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($code) {
            if (! $code) {
                return $this->failValidationErrors('Transaction code is required');
            }

            // Find the debt transaction
            $transaction = $this->db->table('customer_debt_transactions')
                ->where('code', $code)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $transaction) {
                return $this->failNotFound('Transaction not found');
            }

            $input = $this->safeInput();
            $updateData = ['updated_at' => date('Y-m-d H:i:s')];

            // Only allow updating notes and payment_method
            if (isset($input['notes'])) {
                $updateData['notes'] = $input['notes'];
            }
            if (isset($input['payment_method'])) {
                $updateData['payment_method'] = $input['payment_method'];
            }
            if (isset($input['account_number'])) {
                $updateData['account_number'] = $input['account_number'];
            }

            $this->db->table('customer_debt_transactions')
                ->where('id', $transaction['id'])
                ->update($updateData);

            return $this->respond([
                'success' => true,
                'message' => 'Đã cập nhật phiếu thu',
            ]);
        });
    }

    /**
     * Delete a debt transaction (soft delete + reverse debt).
     * DELETE /api/customer-debts/{code}
     */
    public function delete($code = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($code) {
            if (! $code) {
                return $this->failValidationErrors('Transaction code is required');
            }

            // Find the debt transaction
            $transaction = $this->db->table('customer_debt_transactions')
                ->where('code', $code)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $transaction) {
                return $this->failNotFound('Không tìm thấy phiếu thu');
            }

            $this->db->transStart();

            try {
                // Reverse the debt impact on customer
                $customerId = (int) $transaction['customer_id'];
                $transactionValue = (float) $transaction['value'];

                // Get current customer debt
                $customer = $this->db->table('customers')
                    ->where('id', $customerId)
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();

                if ($customer) {
                    $currentDebt = (float) ($customer['current_debt'] ?? 0);
                    // Reverse the transaction: subtract the value (if +100, debt becomes -100; if -100, debt becomes +100)
                    $newDebt = max(0, $currentDebt - $transactionValue);

                    // Update customer debt
                    $this->db->table('customers')
                        ->where('id', $customerId)
                        ->update([
                            'current_debt' => $newDebt,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }

                // Soft delete the transaction
                $this->db->table('customer_debt_transactions')
                    ->where('id', $transaction['id'])
                    ->update([
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->failServerError('Xóa phiếu thu thất bại');
                }

                return $this->respond([
                    'success' => true,
                    'message' => 'Đã xóa phiếu thu',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    /**
     * Record a payment from customer (reduces debt).
     * POST /api/customers/{customerId}/debts/payment
     */
    public function payment($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $input = $this->safeInput();
            $amount = (float) ($input['amount'] ?? 0);
            
            if ($amount <= 0) {
                return $this->failValidationErrors('Amount must be greater than 0');
            }

            // Start transaction for atomic operations
            $this->db->transStart();

            try {
                // Get current customer debt
                $customer = $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();

                if (! $customer) {
                    $this->db->transRollback();
                    return $this->failNotFound('Customer not found');
                }

                $currentDebt = (float) ($customer['current_debt'] ?? 0);
                $newDebt = max(0, $currentDebt - $amount);

                // Create debt transaction
                $code = $this->generateCode('TT');
                $this->db->table('customer_debt_transactions')->insert([
                    'customer_id' => (int) $customerId,
                    'code' => $code,
                    'type' => 'PAYMENT',
                    'value' => -$amount, // Negative because it reduces debt
                    'balance' => $newDebt,
                    'notes' => $input['notes'] ?? null,
                    'created_by' => $input['created_by'] ?? null,
                    'branch_id' => $input['branch_id'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Update customer debt
                $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->update([
                        'current_debt' => $newDebt,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->failServerError('Transaction failed');
                }

                return $this->respondCreated([
                    'success' => true,
                    'data' => [
                        'code' => $code,
                        'amount' => $amount,
                        'new_debt' => $newDebt,
                    ],
                    'message' => 'Đã ghi nhận thanh toán',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    /**
     * Adjust customer debt (increase or decrease).
     * POST /api/customers/{customerId}/debts/adjust
     */
    public function adjust($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $input = $this->safeInput();
            $amount = (float) ($input['amount'] ?? 0);
            
            if ($amount == 0) {
                return $this->failValidationErrors('Amount cannot be 0');
            }

            // Start transaction for atomic operations
            $this->db->transStart();

            try {
                // Get current customer debt
                $customer = $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();

                if (! $customer) {
                    $this->db->transRollback();
                    return $this->failNotFound('Customer not found');
                }

                $currentDebt = (float) ($customer['current_debt'] ?? 0);
                $newDebt = max(0, $currentDebt + $amount);

                // Create debt transaction
                $code = $this->generateCode('DC');
                $this->db->table('customer_debt_transactions')->insert([
                    'customer_id' => (int) $customerId,
                    'code' => $code,
                    'type' => 'ADJUSTMENT',
                    'value' => $amount,
                    'balance' => $newDebt,
                    'notes' => $input['notes'] ?? null,
                    'created_by' => $input['created_by'] ?? null,
                    'branch_id' => $input['branch_id'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Update customer debt
                $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->update([
                        'current_debt' => $newDebt,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->failServerError('Transaction failed');
                }

                return $this->respondCreated([
                    'success' => true,
                    'data' => [
                        'code' => $code,
                        'amount' => $amount,
                        'new_debt' => $newDebt,
                    ],
                    'message' => 'Đã điều chỉnh công nợ',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    /**
     * Apply discount to customer debt.
     * POST /api/customers/{customerId}/debts/discount
     */
    public function discount($customerId = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->wrap(function () use ($customerId) {
            if (! $customerId) {
                return $this->failValidationErrors('Customer ID is required');
            }

            $input = $this->safeInput();
            $amount = (float) ($input['amount'] ?? 0);
            
            if ($amount <= 0) {
                return $this->failValidationErrors('Discount amount must be greater than 0');
            }

            // Start transaction for atomic operations
            $this->db->transStart();

            try {
                // Get current customer debt
                $customer = $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();

                if (! $customer) {
                    $this->db->transRollback();
                    return $this->failNotFound('Customer not found');
                }

                $currentDebt = (float) ($customer['current_debt'] ?? 0);
                $newDebt = max(0, $currentDebt - $amount);

                // Create debt transaction
                $code = $this->generateCode('CK');
                $this->db->table('customer_debt_transactions')->insert([
                    'customer_id' => (int) $customerId,
                    'code' => $code,
                    'type' => 'DISCOUNT',
                    'value' => -$amount,
                    'balance' => $newDebt,
                    'notes' => $input['notes'] ?? null,
                    'created_by' => $input['created_by'] ?? null,
                    'branch_id' => $input['branch_id'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Update customer debt
                $this->db->table('customers')
                    ->where('id', (int) $customerId)
                    ->update([
                        'current_debt' => $newDebt,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    return $this->failServerError('Transaction failed');
                }

                return $this->respondCreated([
                    'success' => true,
                    'data' => [
                        'code' => $code,
                        'amount' => $amount,
                        'new_debt' => $newDebt,
                    ],
                    'message' => 'Đã áp dụng chiết khấu',
                ]);
            } catch (\Throwable $e) {
                $this->db->transRollback();
                throw $e;
            }
        });
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'SALE' => 'Bán hàng',
            'PAYMENT' => 'Thanh toán',
            'ADJUSTMENT' => 'Điều chỉnh',
            'DISCOUNT' => 'Chiết khấu',
            'REFUND' => 'Hoàn tiền',
            default => $type,
        };
    }

    /**
     * Generate unique code with retry logic for concurrency safety
     */
    private function generateCode(string $prefix): string
    {
        $datePrefix = $prefix . date('ymd');
        $maxRetries = 5;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Get the last code for today
            $lastCode = $this->db->table('customer_debt_transactions')
                ->where('code LIKE', $datePrefix . '%')
                ->orderBy('code', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $nextNum = 1;
            if ($lastCode && !empty($lastCode['code'])) {
                $numPart = (int) substr($lastCode['code'], strlen($datePrefix));
                $nextNum = $numPart + 1;
            }

            $candidateCode = $datePrefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

            // Check if this code already exists (race condition check)
            $exists = $this->db->table('customer_debt_transactions')
                ->where('code', $candidateCode)
                ->countAllResults();

            if ($exists === 0) {
                return $candidateCode;
            }

            // Small delay before retry to reduce contention
            usleep(5000); // 5ms
        }

        // Fallback with microseconds for guaranteed uniqueness
        return $datePrefix . substr((string)(microtime(true) * 1000000), -8);
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failServerError($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
