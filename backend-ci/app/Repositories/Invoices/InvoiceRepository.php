<?php

namespace App\Repositories\Invoices;

use App\Models\InvoiceModel;
use App\Models\InvoiceOrderModel;
use App\Models\BranchModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Invoice persistence.
 *
 * @agent-repository: Invoices
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class InvoiceRepository
{
    protected InvoiceModel $invoices;
    protected InvoiceOrderModel $invoiceOrders;
    protected BranchModel $branches;
    protected BaseConnection $db;

    public function __construct(
        ?InvoiceModel $invoices = null,
        ?InvoiceOrderModel $invoiceOrders = null,
        ?BaseConnection $db = null,
        ?BranchModel $branches = null
    ) {
        $this->invoices = $invoices ?? new InvoiceModel();
        $this->invoiceOrders = $invoiceOrders ?? new InvoiceOrderModel();
        $this->db = $db ?? \Config\Database::connect();
        $this->branches = $branches ?? new BranchModel();
    }

    /** Generate next invoice number per branch (transaction safe). */
    public function nextNumber(int $branchId, ?string $issueDate = null): string
    {
        $issueDate = $issueDate ?: date('Y-m-d');
        $datePart = date('ymd', strtotime($issueDate));
        $branchCode = $this->branchCode($branchId) ?: 'BR' . $branchId;

        $this->db->transStart();
        $sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->db->protectIdentifiers($this->invoices->table) . ' WHERE branch_id = ? AND issue_date = ?';
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= ' FOR UPDATE';
        }
        $row = $this->db->query($sql, [$branchId, $issueDate])->getRowArray();
        $count = (int) ($row['cnt'] ?? 0);
        $number = sprintf('%s%s%05d', $branchCode, $datePart, $count + 1);
        $this->db->transComplete();
        return $number;
    }

    /** Create invoice with mappings. */
    public function create(array $invoice, array $orderIds): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $invoice + [
            'created_at' => $invoice['created_at'] ?? $now,
            'updated_at' => $invoice['updated_at'] ?? $now,
        ];

        $this->db->transStart();
        $this->invoices->insert($payload);
        $invoiceId = (int) $this->invoices->getInsertID();

        if ($orderIds) {
            $rows = [];
            foreach ($orderIds as $oid) {
                $rows[] = [
                    'invoice_id' => $invoiceId,
                    'order_id' => $oid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('invoice_orders')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $this->findById($invoiceId) ?? ($payload + ['id' => $invoiceId, 'orders' => $orderIds]);
    }

    /** List invoices with filters + pagination. */
    public function findAll(array $filters): array
    {
        $b = $this->invoices->builder();
        $b->select('invoices.*, customers.name as customer_name, users.full_name as created_by_name')
            ->join('customers', 'customers.id = invoices.customer_id', 'left')
            ->join('users', 'users.id = invoices.created_by', 'left');

        $this->applyFiltersToBuilder($b, $filters);

        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $rows = $b->orderBy('invoices.issue_date', 'DESC')
            ->orderBy('invoices.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    public function totals(array $filters): array
    {
        $b = $this->applyFilters($filters);
        $row = $b->selectSum('customer_payable')
            ->selectSum('customer_paid')
            ->selectSum('cod_amount')
            ->selectSum('shipping_fee')
            ->get()
            ->getRowArray() ?: [];
        return [
            'customer_payable' => (float) ($row['customer_payable'] ?? 0),
            'customer_paid' => (float) ($row['customer_paid'] ?? 0),
            'cod_amount' => (float) ($row['cod_amount'] ?? 0),
            'shipping_fee' => (float) ($row['shipping_fee'] ?? 0),
        ];
    }

    public function findById(int $id): ?array
    {
        $row = $this->invoices->find($id);
        if (! $row) {
            return null;
        }
        $row = $this->hydrate($row);
        $row['orders'] = $this->invoiceOrders
            ->where('invoice_id', $id)
            ->findAll();
        if ($this->db->tableExists('order_payments')) {
            $query = $this->db->table('order_payments op')
                ->select('op.id, op.order_id, op.method, op.amount, op.status, op.ref_code, op.paid_at')
                ->join('invoice_orders io', 'io.order_id = op.order_id')
                ->where('io.invoice_id', $id)
                ->orderBy('op.paid_at', 'DESC')
                ->get();
            $row['payments'] = $query ? $query->getResultArray() : [];
        }
        return $row;
    }

    /** Check if any of the orders already linked to an invoice. */
    public function findExistingInvoiceForOrders(array $orderIds): ?array
    {
        if (empty($orderIds)) {
            return null;
        }
        $row = $this->invoiceOrders->builder()
            ->select('invoice_id, order_id')
            ->whereIn('order_id', $orderIds)
            ->get(1)
            ->getRowArray();
        if (! $row) {
            return null;
        }
        $invoice = $this->findById((int) $row['invoice_id']);
        return $invoice;
    }

    /** Fetch orders (id,total,customer,branch,status) used for calculations. */
    public function ordersByIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $rows = $this->db->table('orders')
            ->select('id, customer_id, branch_id, total, status')
            ->whereIn('id', $orderIds)
            ->get()
            ->getResultArray();
        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'customer_id' => $r['customer_id'] !== null ? (int) $r['customer_id'] : null,
            'branch_id' => $r['branch_id'] !== null ? (int) $r['branch_id'] : null,
            'total' => (float) $r['total'],
            'status' => $r['status'] ?? null,
        ], $rows);
    }

    /** Persist pdf path. */
    public function updatePdfPath(int $invoiceId, string $path): void
    {
        $this->invoices->update($invoiceId, [
            'pdf_path' => $path,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function applyFilters(array $filters)
    {
        $b = $this->invoices->builder();
        $this->applyFiltersToBuilder($b, $filters);
        return $b;
    }

    private function applyFiltersToBuilder($b, array $filters): void
    {
        if (! empty($filters['customer_id'])) {
            $b->where('invoices.customer_id', $filters['customer_id']);
        }
        if (! empty($filters['branch_id'])) {
            $b->where('invoices.branch_id', $filters['branch_id']);
        }
        if (! empty($filters['invoice_status'])) {
            $b->where('invoices.invoice_status', $filters['invoice_status']);
        }
        if (! empty($filters['invoice_type'])) {
            $b->where('invoices.invoice_type', $filters['invoice_type']);
        }
        if (! empty($filters['e_invoice_status'])) {
            $b->where('invoices.e_invoice_status', $filters['e_invoice_status']);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $b->groupStart()
                ->like('invoices.invoice_number', $s)
                ->orLike('invoices.notes', $s)
                ->orLike('customers.name', $s)
                ->groupEnd();
        }
        if (! empty($filters['issue_date_from'])) {
            $b->where('invoices.issue_date >=', $filters['issue_date_from']);
        }
        if (! empty($filters['issue_date_to'])) {
            $b->where('invoices.issue_date <=', $filters['issue_date_to']);
        }
    }

    private function hydrate(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['invoice_status'] = $row['invoice_status'] ?? null;
        $row['invoice_type'] = $row['invoice_type'] ?? null;
        $row['e_invoice_status'] = $row['e_invoice_status'] ?? null;
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0.0;
        $row['vat_rate'] = isset($row['vat_rate']) ? (float) $row['vat_rate'] : 0.0;
        $row['vat_amount'] = isset($row['vat_amount']) ? (float) $row['vat_amount'] : 0.0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        $row['goods_total'] = isset($row['goods_total']) ? (float) $row['goods_total'] : 0.0;
        $row['discount_total'] = isset($row['discount_total']) ? (float) $row['discount_total'] : 0.0;
        $row['net_total'] = isset($row['net_total']) ? (float) $row['net_total'] : 0.0;
        $row['tax_amount'] = isset($row['tax_amount']) ? (float) $row['tax_amount'] : 0.0;
        $row['other_fee'] = isset($row['other_fee']) ? (float) $row['other_fee'] : 0.0;
        $row['shipping_fee'] = isset($row['shipping_fee']) ? (float) $row['shipping_fee'] : 0.0;
        $row['customer_payable'] = isset($row['customer_payable']) ? (float) $row['customer_payable'] : 0.0;
        $row['customer_paid'] = isset($row['customer_paid']) ? (float) $row['customer_paid'] : 0.0;
        $row['cod_amount'] = isset($row['cod_amount']) ? (float) $row['cod_amount'] : 0.0;
        $row['rounding_adjustment'] = isset($row['rounding_adjustment']) ? (float) $row['rounding_adjustment'] : 0.0;
        $row['total_paid'] = isset($row['total_paid']) ? (float) $row['total_paid'] : 0.0;
        $row['payment_status'] = $row['payment_status'] ?? null;
        $row['last_payment_date'] = $row['last_payment_date'] ?? null;
        return $row;
    }

    private function branchCode(int $branchId): ?string
    {
        $row = $this->branches->select('code')->find($branchId);
        return $row['code'] ?? null;
    }
}
