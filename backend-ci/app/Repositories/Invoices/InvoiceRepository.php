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
        $b->select('invoices.*,
                customers.name as customer_name,
                customers.code as customer_code,
                customers.phone as phone,
                customers.email as email,
                customers.address as address,
                customers.province as region,
                customers.ward as ward,
                branches.name as branch_name,
                users.full_name as created_by_name')
            ->join('customers', 'customers.id = invoices.customer_id', 'left')
            ->join('branches', 'branches.id = invoices.branch_id', 'left')
            ->join('users', 'users.id = invoices.created_by', 'left')
            ->where('invoices.deleted_at', null);

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
        // Use standard find first to ensure we match the record
        $row = $this->invoices->find($id);
        if (! $row) {
            return null;
        }
        
        $row = $this->hydrate($row);
        
        // Enrich with Customer data
        if (!empty($row['customer_id'])) {
            $customer = $this->db->table('customers')->where('id', $row['customer_id'])->get()->getRowArray();
            if ($customer) {
                $row['customer_name'] = $customer['name'] ?? null;
                $row['customer_code'] = $customer['code'] ?? null;
                $row['phone'] = $customer['phone'] ?? null;
                $row['email'] = $customer['email'] ?? null;
                // Try to handle address safely
                $row['address'] = $customer['address'] ?? null; 
                $row['region'] = $customer['province'] ?? null;
                $row['ward'] = $customer['ward'] ?? null;
            }
        }
        
        // Fallback for customer name if still empty (from backfilled column)
        if (empty($row['customer_name']) && !empty($row['customer_name'])) {
             // Already mapped by hydration but let's be explicit if needed or if find() didn't get it?
             // find() gets all columns, so $row['customer_name'] should exist if column exists.
             // But if logic above overwrote it with null?
             // No, logic above only runs if customer_id is not empty.
        }
        
        // If customer_id was null, $row['customer_name'] holds the backfilled value from DB column.
        // If customer_id was NOT null but lookup failed, we might want to fallback?
        if (empty($row['customer_name']) && isset($row['customer_name'])) {
            // keep it
        }
        
        // Enrich with Creator data
        if (!empty($row['created_by'])) {
            $user = $this->db->table('users')->select('full_name')->where('id', $row['created_by'])->get()->getRow();
            $row['created_by_name'] = $user ? $user->full_name : null;
        }

        // Enrich with Branch name
        if (!empty($row['branch_id'])) {
            $branch = $this->db->table('branches')->select('name')->where('id', $row['branch_id'])->get()->getRow();
            $row['branch_name'] = $branch ? $branch->name : null;
        }

        $row['orders'] = $this->invoiceOrders
            ->where('invoice_id', $id)
            ->findAll();
        
        // Get order IDs for related queries
        $orderIds = array_column($row['orders'], 'order_id');
        
        // Fetch order items (products) from linked orders
        if (!empty($orderIds) && $this->db->tableExists('order_items')) {
            $query = $this->db->table('order_items oi')
                ->select('oi.*, p.name as product_name, p.sku as product_code')
                ->join('products p', 'p.id = oi.product_id', 'left')
                ->whereIn('oi.order_id', $orderIds)
                ->orderBy('oi.id', 'ASC')
                ->get();
            $row['items'] = $query ? $query->getResultArray() : [];
        } else {
            $row['items'] = [];
        }
        
        // Fetch payments
        if (!empty($orderIds) && $this->db->tableExists('order_payments')) {
            $query = $this->db->table('order_payments op')
                ->select('op.id, op.order_id, op.method as payment_method, op.amount, op.status, op.ref_code as code, op.paid_at as created_at, u.full_name as creator_name')
                ->join('users u', 'u.id = op.created_by', 'left')
                ->whereIn('op.order_id', $orderIds)
                ->orderBy('op.paid_at', 'DESC')
                ->get();
            $row['payments'] = $query ? $query->getResultArray() : [];
        } else {
            // BACKFILL: For imported invoices with no linked payments, simulate from total
            if (($row['customer_paid'] ?? 0) > 0) {
                $row['payments'] = [[
                    'id' => 0,
                    'code' => 'TTH-IMP-' . $row['id'],
                    'created_at' => $row['issue_date'] ?? $row['created_at'],
                    'creator_name' => $row['created_by_name'] ?? 'System',
                    'payment_method' => 'Tiền mặt',
                    'amount' => (float) $row['customer_paid'],
                    'status' => 'Đã thanh toán',
                    'cash_amount' => (float) $row['customer_paid']
                ]];
            } else {
                $row['payments'] = [];
            }
        }
        
        // Fetch delivery history/tracking if table exists
        $deliveryFound = false;
        if ($this->db->tableExists('delivery_tracking')) {
            $query = $this->db->table('delivery_tracking dt')
                ->select('dt.*')
                ->where('dt.invoice_id', $id)
                ->orderBy('dt.created_at', 'DESC')
                ->get();
            $results = $query ? $query->getResultArray() : [];
            if (!empty($results)) {
                $row['delivery_history'] = $results;
                $deliveryFound = true;
            }
        }
        
        if (!$deliveryFound) {
            // BACKFILL: For imported invoices, simulate history from status
            if (!empty($row['delivery_status'])) {
                 $row['delivery_history'] = [[
                    'id' => 0,
                    'time' => $row['delivery_time'] ?? $row['issue_date'] ?? $row['created_at'],
                    'location' => 'KiotViet Import',
                    'message' => 'Trạng thái: ' . ($row['delivery_status'] ?? 'Unknown') . ($row['delivery_note'] ? ' - ' . $row['delivery_note'] : '')
                ]];
            } else {
                $row['delivery_history'] = [];
            }
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

    /** Update invoice. */
    public function update(int $id, array $data): bool
    {
        $payload = $data + ['updated_at' => date('Y-m-d H:i:s')];
        return (bool) $this->invoices->update($id, $payload);
    }

    /** Soft delete invoice. */
    public function softDelete(int $id): bool
    {
        return (bool) $this->invoices->update($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function applyFilters(array $filters)
    {
        $b = $this->invoices->builder();
        // Join customers table for search filter on customer name/phone
        $b->join('customers', 'customers.id = invoices.customer_id', 'left');
        $b->where('invoices.deleted_at', null);
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

        // Support single status or array of statuses
        if (! empty($filters['invoice_status'])) {
            $statuses = is_array($filters['invoice_status'])
                ? $filters['invoice_status']
                : [$filters['invoice_status']];
            $b->whereIn('invoices.invoice_status', $statuses);
        }

        // Support single type or array of types
        if (! empty($filters['invoice_type'])) {
            $b->where('invoices.invoice_type', $filters['invoice_type']);
        }
        if (! empty($filters['invoice_types'])) {
            $types = is_array($filters['invoice_types'])
                ? $filters['invoice_types']
                : [$filters['invoice_types']];
            $b->whereIn('invoices.invoice_type', $types);
        }

        if (! empty($filters['e_invoice_status'])) {
            $b->where('invoices.e_invoice_status', $filters['e_invoice_status']);
        }
        if (! empty($filters['delivery_status'])) {
            $b->where('invoices.delivery_status', $filters['delivery_status']);
        }
        if (! empty($filters['shipping_partner'])) {
            $b->where('invoices.shipping_partner', $filters['shipping_partner']);
        }
        if (! empty($filters['payment_method'])) {
            $b->where('invoices.payment_method', $filters['payment_method']);
        }
        if (! empty($filters['sales_channel'])) {
            $b->where('invoices.sales_channel', $filters['sales_channel']);
        }
        if (! empty($filters['created_by'])) {
            $b->where('invoices.created_by', $filters['created_by']);
        }
        if (! empty($filters['seller_id'])) {
            $b->where('invoices.seller_id', $filters['seller_id']);
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $b->groupStart()
                ->like('invoices.invoice_number', $s)
                ->orLike('invoices.notes', $s)
                ->orLike('invoices.shipment_code', $s)
                ->orLike('customers.name', $s)
                ->orLike('customers.phone', $s)
                ->groupEnd();
        }

        // Date filters (support both naming conventions)
        $dateFrom = $filters['issue_date_from'] ?? $filters['date_from'] ?? null;
        $dateTo = $filters['issue_date_to'] ?? $filters['date_to'] ?? null;
        if (! empty($dateFrom)) {
            $b->where('invoices.issue_date >=', $dateFrom);
        }
        if (! empty($dateTo)) {
            $b->where('invoices.issue_date <=', $dateTo);
        }

        // Shipping time filters
        if (! empty($filters['shipping_time_from'])) {
            $b->where('invoices.delivery_time >=', $filters['shipping_time_from']);
        }
        if (! empty($filters['shipping_time_to'])) {
            $b->where('invoices.delivery_time <=', $filters['shipping_time_to']);
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
