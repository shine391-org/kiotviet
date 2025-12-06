<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Repositories\Partners\PartnerRepository;
use App\Models\SupplierDebtTransactionModel;
use App\Models\PartnerModel;
use CodeIgniter\API\ResponseTrait;

/**
 * Suppliers API Controller.
 * @agent-controller: Suppliers
 * @agent-pattern: Thin controller with repository
 */
class SuppliersController extends BaseController
{
    use ResponseTrait;

    protected PartnerRepository $repo;
    protected SupplierDebtTransactionModel $debtModel;
    protected PartnerModel $partnerModel;

    public function __construct()
    {
        $this->repo = new PartnerRepository();
        $this->debtModel = new SupplierDebtTransactionModel();
        $this->partnerModel = new PartnerModel();
    }

    /**
     * List suppliers with filters and pagination.
     * GET /api/suppliers
     */
    public function index()
    {
        return $this->wrap(function () {
            $filters = $this->request->getGet();
            $filters['type'] = 'supplier'; // Force supplier type

            $items = $this->repo->findAll($filters);
            $total = $this->repo->count($filters);
            $summary = $this->repo->getSummary($filters);

            $limit = (int) ($filters['limit'] ?? 15);
            $page = (int) ($filters['page'] ?? 1);

            return $this->respond([
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
                'summary' => $summary,
            ]);
        });
    }

    /**
     * Get single supplier with receipts and payables.
     * GET /api/suppliers/{id}
     */
    public function show($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            // Get receipts (lịch sử nhập/trả hàng) from purchase_orders + goods_receipts
            $receipts = $this->getSupplierReceipts((int) $id);
            
            // Get payables (công nợ) from supplier_debt_transactions
            $payables = $this->getSupplierPayables((int) $id);

            $supplier['receipts'] = $receipts;
            $supplier['payables'] = $payables;

            return $this->respond(['data' => $supplier]);
        });
    }

    /**
     * Get supplier receipts (purchase orders + goods receipts).
     */
    private function getSupplierReceipts(int $partnerId): array
    {
        $db = \Config\Database::connect();
        
        // Get purchase orders for this supplier
        $orders = $db->table('purchase_orders po')
            ->select('po.order_number as code, po.order_date as time, po.total, po.status, u.full_name as creator, b.name as branch')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left')
            ->where('po.partner_id', $partnerId)
            ->orWhere('po.supplier_id', $partnerId)
            ->orderBy('po.order_date', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return array_map(function ($row) {
            return [
                'code' => $row['code'] ?? '',
                'time' => $row['time'] ? date('d/m/Y H:i', strtotime($row['time'])) : '',
                'creator' => $row['creator'] ?? '',
                'branch' => $row['branch'] ?? '',
                'total' => (float) ($row['total'] ?? 0),
                'status' => $this->mapPOStatus($row['status'] ?? ''),
            ];
        }, $orders);
    }

    /**
     * Get supplier payables (debt transactions).
     */
    private function getSupplierPayables(int $partnerId): array
    {
        $transactions = $this->debtModel
            ->where('partner_id', $partnerId)
            ->orderBy('transaction_date', 'DESC')
            ->limit(50)
            ->findAll();

        return array_map(function ($row) {
            $type = $row['type'] ?? '';
            $typeLabel = match ($type) {
                'adjust' => 'Điều chỉnh',
                'payment' => 'Thanh toán',
                'discount' => 'Chiết khấu',
                default => 'Nhập hàng',
            };

            // For payment/discount, value is negative (reduces debt)
            $value = (float) ($row['amount'] ?? 0);
            if (in_array($type, ['payment', 'discount'])) {
                $value = -$value;
            }

            return [
                'code' => $row['reference_type'] ? strtoupper(substr($row['reference_type'], 0, 2)) . '-' . ($row['id'] ?? '') : 'TX-' . ($row['id'] ?? ''),
                'time' => $row['transaction_date'] ? date('d/m/Y H:i', strtotime($row['transaction_date'])) : '',
                'type' => $typeLabel,
                'value' => $value,
                'payable' => (float) ($row['debt_after'] ?? 0),
            ];
        }, $transactions);
    }

    /**
     * Map PO status to Vietnamese.
     */
    private function mapPOStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Nháp',
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'in_transit' => 'Đang vận chuyển',
            'received' => 'Đã nhận hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }

    /**
     * Create new supplier.
     * POST /api/suppliers
     */
    public function create()
    {
        return $this->wrap(function () {
            $data = $this->safeInput();
            $data['type'] = 'supplier';

            // Validate required fields
            if (empty($data['name'])) {
                return $this->failValidationErrors('Tên nhà cung cấp là bắt buộc');
            }

            $supplier = $this->repo->create($data);
            return $this->respondCreated(['data' => $supplier]);
        });
    }

    /**
     * Update supplier.
     * PUT /api/suppliers/{id}
     */
    public function update($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $data = $this->safeInput();
            $this->repo->update((int) $id, $data);

            return $this->respond([
                'data' => $this->repo->findById((int) $id),
                'message' => 'Cập nhật thành công',
            ]);
        });
    }

    /**
     * Delete supplier.
     * DELETE /api/suppliers/{id}
     */
    public function delete($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $this->repo->delete((int) $id);

            return $this->respond([
                'success' => true,
                'message' => 'Đã xóa nhà cung cấp',
            ]);
        });
    }

    /**
     * Adjust supplier debt.
     * POST /api/suppliers/{id}/adjust
     */
    public function adjust($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $data = $this->safeInput();
            
            if (!isset($data['adjust_value']) || !is_numeric($data['adjust_value'])) {
                return $this->failValidationErrors('Giá trị điều chỉnh là bắt buộc');
            }

            $adjustValue = (float) $data['adjust_value'];
            $debtBefore = (float) ($supplier['current_debt'] ?? $supplier['debt_amount'] ?? 0);
            $debtAfter = $adjustValue; // Set new debt value

            // Create transaction record
            $transaction = [
                'partner_id' => (int) $id,
                'type' => 'adjust',
                'amount' => $adjustValue - $debtBefore,
                'debt_before' => $debtBefore,
                'debt_after' => $debtAfter,
                'note' => $data['description'] ?? null,
                'transaction_date' => $data['adjust_date'] ?? date('Y-m-d H:i:s'),
            ];
            $this->debtModel->insert($transaction);

            // Update supplier debt
            $this->partnerModel->update((int) $id, ['debt_amount' => $debtAfter]);

            return $this->respond([
                'success' => true,
                'message' => 'Điều chỉnh công nợ thành công',
                'data' => [
                    'debt_before' => $debtBefore,
                    'debt_after' => $debtAfter,
                    'transaction_id' => $this->debtModel->getInsertID(),
                ],
            ]);
        });
    }

    /**
     * Create payment for supplier debt.
     * POST /api/suppliers/{id}/payment
     */
    public function payment($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $data = $this->safeInput();
            
            if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
                return $this->failValidationErrors('Số tiền thanh toán là bắt buộc và phải lớn hơn 0');
            }

            $paymentAmount = (float) $data['amount'];
            $debtBefore = (float) ($supplier['current_debt'] ?? $supplier['debt_amount'] ?? 0);
            $debtAfter = max(0, $debtBefore - $paymentAmount);

            // Create transaction record
            $transaction = [
                'partner_id' => (int) $id,
                'type' => 'payment',
                'amount' => $paymentAmount,
                'debt_before' => $debtBefore,
                'debt_after' => $debtAfter,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['payment_date'] ?? date('Y-m-d H:i:s'),
            ];
            $this->debtModel->insert($transaction);

            // Update supplier debt
            $this->partnerModel->update((int) $id, ['debt_amount' => $debtAfter]);

            return $this->respond([
                'success' => true,
                'message' => 'Thanh toán thành công',
                'data' => [
                    'payment_amount' => $paymentAmount,
                    'debt_before' => $debtBefore,
                    'debt_after' => $debtAfter,
                    'transaction_id' => $this->debtModel->getInsertID(),
                ],
            ]);
        });
    }

    /**
     * Apply discount for supplier.
     * POST /api/suppliers/{id}/discount
     */
    public function discount($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $data = $this->safeInput();
            
            if (!isset($data['discount_amount']) || !is_numeric($data['discount_amount']) || $data['discount_amount'] <= 0) {
                return $this->failValidationErrors('Số tiền chiết khấu là bắt buộc và phải lớn hơn 0');
            }

            $discountAmount = (float) $data['discount_amount'];
            $debtBefore = (float) ($supplier['current_debt'] ?? $supplier['debt_amount'] ?? 0);
            $debtAfter = max(0, $debtBefore - $discountAmount);

            // Create transaction record
            $transaction = [
                'partner_id' => (int) $id,
                'type' => 'discount',
                'amount' => $discountAmount,
                'debt_before' => $debtBefore,
                'debt_after' => $debtAfter,
                'note' => $data['note'] ?? null,
                'transaction_date' => $data['discount_date'] ?? date('Y-m-d H:i:s'),
            ];
            $this->debtModel->insert($transaction);

            // Update supplier debt
            $this->partnerModel->update((int) $id, ['debt_amount' => $debtAfter]);

            return $this->respond([
                'success' => true,
                'message' => 'Tạo chiết khấu thành công',
                'data' => [
                    'discount_amount' => $discountAmount,
                    'debt_before' => $debtBefore,
                    'debt_after' => $debtAfter,
                    'transaction_id' => $this->debtModel->getInsertID(),
                ],
            ]);
        });
    }

    /**
     * Get supplier debt history.
     * GET /api/suppliers/{id}/debt-history
     */
    public function debtHistory($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $limit = (int) ($this->request->getGet('limit') ?? 50);
            $transactions = $this->debtModel->getByPartnerId((int) $id, $limit);

            return $this->respond([
                'data' => $transactions,
                'current_debt' => $supplier['current_debt'] ?? $supplier['debt_amount'] ?? 0,
            ]);
        });
    }

    // ============== EXPORT / IMPORT ==============

    /**
     * Export suppliers list to Excel.
     * GET /api/suppliers/export
     */
    public function export()
    {
        return $this->wrap(function () {
            $filters = $this->request->getGet() ?? [];
            $exportService = new \App\Services\Suppliers\SupplierExportService();
            $filepath = $exportService->exportSuppliers($filters);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="suppliers_' . date('Ymd_His') . '.xlsx"')
                ->setBody(file_get_contents($filepath));
        });
    }

    /**
     * Export supplier receipts (purchase history) to Excel.
     * GET /api/suppliers/{id}/export-receipts
     */
    public function exportReceipts($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $exportService = new \App\Services\Suppliers\SupplierExportService();
            $filepath = $exportService->exportReceipts((int) $id);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="supplier_receipts_' . $supplier['code'] . '_' . date('Ymd_His') . '.xlsx"')
                ->setBody(file_get_contents($filepath));
        });
    }

    /**
     * Export supplier payables (debt history) to Excel.
     * GET /api/suppliers/{id}/export-payables
     */
    public function exportPayables($id = null)
    {
        return $this->wrap(function () use ($id) {
            $supplier = $this->repo->findById((int) $id);
            if (!$supplier) {
                return $this->failNotFound('Supplier not found');
            }

            $exportService = new \App\Services\Suppliers\SupplierExportService();
            $filepath = $exportService->exportPayables((int) $id);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="supplier_payables_' . $supplier['code'] . '_' . date('Ymd_His') . '.xlsx"')
                ->setBody(file_get_contents($filepath));
        });
    }

    /**
     * Get import template.
     * GET /api/suppliers/import-template
     */
    public function importTemplate()
    {
        return $this->wrap(function () {
            $importService = new \App\Services\Suppliers\SupplierImportService();
            $filepath = $importService->getTemplate();

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="supplier_import_template.xlsx"')
                ->setBody(file_get_contents($filepath));
        });
    }

    /**
     * Import suppliers from Excel.
     * POST /api/suppliers/import
     */
    public function import()
    {
        return $this->wrap(function () {
            $file = $this->request->getFile('file');
            
            if (!$file || !$file->isValid()) {
                return $this->failValidationErrors('Vui lòng chọn file Excel hợp lệ');
            }

            $ext = $file->getClientExtension();
            if (!in_array($ext, ['xlsx', 'xls'])) {
                return $this->failValidationErrors('File phải có định dạng .xlsx hoặc .xls');
            }

            $filepath = $file->getTempName();
            $importService = new \App\Services\Suppliers\SupplierImportService();
            $result = $importService->importFromExcel($filepath);

            return $this->respond([
                'success' => true,
                'message' => "Import hoàn tất: {$result['success']}/{$result['total']} thành công",
                'data' => $result,
            ]);
        });
    }

    /**
     * Shared try/catch wrapper.
     */
    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Safely fetch request body supporting JSON or form-data.
     */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
