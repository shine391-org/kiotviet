<?php

namespace App\Repositories\Invoices;

use App\Models\InvoiceModel;
use App\Models\InvoiceOrderModel;
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
    protected BaseConnection $db;

    public function __construct(
        ?InvoiceModel $invoices = null,
        ?InvoiceOrderModel $invoiceOrders = null,
        ?BaseConnection $db = null
    ) {
        $this->invoices = $invoices ?? new InvoiceModel();
        $this->invoiceOrders = $invoiceOrders ?? new InvoiceOrderModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Generate next invoice number per branch (transaction safe). */
    public function nextNumber(int $branchId): string
    {
        $this->db->transStart();
        $sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->db->protectIdentifiers($this->invoices->table) . ' WHERE branch_id = ?';
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= ' FOR UPDATE';
        }
        $row = $this->db->query($sql, [$branchId])->getRowArray();
        $count = (int) ($row['cnt'] ?? 0);
        $number = sprintf('HD-%d-%04d', $branchId, $count + 1);
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
        $b = $this->applyFilters($filters);
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $rows = $b->orderBy('issue_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
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

    /** Fetch orders (id,total,customer,branch) used for calculations. */
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
        if (! empty($filters['customer_id'])) {
            $b->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['branch_id'])) {
            $b->where('branch_id', $filters['branch_id']);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $b->groupStart()
                ->like('invoice_number', $s)
                ->orLike('notes', $s)
                ->groupEnd();
        }
        if (! empty($filters['issue_date_from'])) {
            $b->where('issue_date >=', $filters['issue_date_from']);
        }
        if (! empty($filters['issue_date_to'])) {
            $b->where('issue_date <=', $filters['issue_date_to']);
        }
        return $b;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0.0;
        $row['vat_rate'] = isset($row['vat_rate']) ? (float) $row['vat_rate'] : 0.0;
        $row['vat_amount'] = isset($row['vat_amount']) ? (float) $row['vat_amount'] : 0.0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        return $row;
    }
}
