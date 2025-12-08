<?php

namespace App\Repositories\Shipping;

use App\Models\InvoiceModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Shipment repository - queries invoices with delivery info.
 *
 * @agent-repository: Shipments
 * @agent-pattern: Repository pattern
 */
class ShipmentRepository
{
    protected InvoiceModel $invoices;
    protected BaseConnection $db;

    public function __construct(?InvoiceModel $invoices = null, ?BaseConnection $db = null)
    {
        $this->invoices = $invoices ?? new InvoiceModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** List shipments (invoices with delivery info). */
    public function findAll(array $filters): array
    {
        $b = $this->baseQuery();
        $this->applyFilters($b, $filters);

        $limit = $filters['limit'] ?? 15;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $sort = $filters['sort'] ?? 'created_at,desc';
        [$sortField, $sortDir] = explode(',', $sort . ',desc');
        $sortField = $this->mapSortField($sortField);
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $rows = $b->orderBy($sortField, $sortDir)
            ->orderBy('invoices.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $rows;
    }

    /** Count shipments. */
    public function count(array $filters): int
    {
        $b = $this->invoices->builder();
        $b->groupStart()
            ->where('invoices.shipment_code IS NOT NULL')
            ->orWhere('invoices.delivery_status IS NOT NULL')
            ->groupEnd();
        $this->applyFilters($b, $filters);
        return $b->countAllResults();
    }

    /** Summary totals. */
    public function summary(array $filters): array
    {
        $b = $this->invoices->builder();
        $b->groupStart()
            ->where('invoices.shipment_code IS NOT NULL')
            ->orWhere('invoices.delivery_status IS NOT NULL')
            ->groupEnd();
        $this->applyFilters($b, $filters);

        $row = $b->selectSum('invoices.cod_amount', 'cod_total')
            ->selectSum('invoices.shipping_fee', 'shipping_total')
            ->get()
            ->getRowArray() ?: [];

        return [
            'cod_total' => (float) ($row['cod_total'] ?? 0),
            'shipping_total' => (float) ($row['shipping_total'] ?? 0),
        ];
    }

    /** Find by invoice ID. */
    public function findById(int $id): ?array
    {
        $b = $this->baseQuery();
        $b->where('invoices.id', $id);
        return $b->get()->getRowArray();
    }

    private function baseQuery()
    {
        $b = $this->invoices->builder();
        $b->select('invoices.id,
                invoices.shipment_code as code,
                invoices.invoice_number as invoice_code,
                invoices.created_at,
                invoices.delivery_status,
                invoices.delivery_time,
                invoices.delivery_note as status_note,
                invoices.shipping_partner as delivery_partner,
                invoices.cod_amount,
                invoices.customer_paid as cod_collected,
                invoices.shipping_fee as partner_fee,
                invoices.branch_id,
                customers.name as customer_name,
                customers.phone,
                customers.address,
                customers.province as area_province,
                customers.district as area_district,
                customers.ward,
                branches.name as branch_name,
                users.full_name as created_by')
            ->join('customers', 'customers.id = invoices.customer_id', 'left')
            ->join('branches', 'branches.id = invoices.branch_id', 'left')
            ->join('users', 'users.id = invoices.created_by', 'left')
            ->groupStart()
            ->where('invoices.shipment_code IS NOT NULL')
            ->orWhere('invoices.delivery_status IS NOT NULL')
            ->groupEnd();

        return $b;
    }

    private function applyFilters($b, array $filters): void
    {
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $b->groupStart()
                ->like('invoices.shipment_code', $s)
                ->orLike('invoices.invoice_number', $s)
                ->orLike('customers.name', $s)
                ->orLike('customers.phone', $s)
                ->groupEnd();
        }

        if (! empty($filters['statuses'])) {
            $statuses = is_array($filters['statuses']) ? $filters['statuses'] : [$filters['statuses']];
            $b->whereIn('invoices.delivery_status', $statuses);
        }

        if (! empty($filters['partners'])) {
            $partners = is_array($filters['partners']) ? $filters['partners'] : [$filters['partners']];
            $b->whereIn('invoices.shipping_partner', $partners);
        }

        if (! empty($filters['branch_id'])) {
            $b->where('invoices.branch_id', $filters['branch_id']);
        }

        if (! empty($filters['branch'])) {
            $b->where('branches.name', $filters['branch']);
        }

        if (! empty($filters['branches'])) {
            $branches = is_array($filters['branches']) ? $filters['branches'] : [$filters['branches']];
            $b->whereIn('branches.name', $branches);
        }

        // Date filters
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;
        if ($createdFrom) {
            $b->where('invoices.created_at >=', $createdFrom . ' 00:00:00');
        }
        if ($createdTo) {
            $b->where('invoices.created_at <=', $createdTo . ' 23:59:59');
        }

        $completedFrom = $filters['completed_from'] ?? null;
        $completedTo = $filters['completed_to'] ?? null;
        if ($completedFrom) {
            $b->where('invoices.delivery_time >=', $completedFrom . ' 00:00:00');
        }
        if ($completedTo) {
            $b->where('invoices.delivery_time <=', $completedTo . ' 23:59:59');
        }

        // COD filter
        $cod = $filters['cod'] ?? 'all';
        if ($cod === 'yes') {
            $b->where('invoices.cod_amount >', 0);
        } elseif ($cod === 'no') {
            $b->groupStart()
                ->where('invoices.cod_amount', 0)
                ->orWhere('invoices.cod_amount IS NULL')
                ->groupEnd();
        }
    }

    private function mapSortField(string $field): string
    {
        $map = [
            'created_at' => 'invoices.created_at',
            'delivery_time' => 'invoices.delivery_time',
            'cod_amount' => 'invoices.cod_amount',
            'code' => 'invoices.shipment_code',
        ];
        return $map[$field] ?? 'invoices.created_at';
    }
}
