<?php

namespace App\Repositories\ProductMedia;

use CodeIgniter\Database\BaseConnection;

/**
 * Product media database operations.
 *
 * @agent-repository: Media library persistence
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ProductMediaRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * List media library with pagination.
     *
     * @agent-use: Media library listing
     * @agent-pattern: Standard query with total
     */
    public function listLibrary(array $filters): array
    {
        $builder = $this->baseSelect();

        $total = $builder->countAllResults(false);
        $data  = $builder->orderBy('pi.created_at', 'DESC')
            ->limit($filters['limit'], $filters['offset'])
            ->get()
            ->getResultArray();

        return ['data' => $data, 'total' => $total];
    }

    /**
     * List media filtered by year/month.
     *
     * @agent-use: Media by date
     * @agent-pattern: Conditional filters
     */
    public function listByDate(array $filters): array
    {
        $builder = $this->baseSelect();

        if (! empty($filters['year'])) {
            $this->applyYearFilter($builder, (int) $filters['year']);
        }

        if (! empty($filters['month'])) {
            $this->applyMonthFilter($builder, (int) $filters['month']);
        }

        $total = $builder->countAllResults(false);

        $data = $builder->orderBy('pi.created_at', 'DESC')
            ->limit($filters['limit'], $filters['offset'])
            ->get()
            ->getResultArray();

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Search images by SKU/product code.
     *
     * @agent-use: Media search by SKU
     * @agent-pattern: Join & filter
     */
    public function searchBySku(array $filters): array
    {
        $builder = $this->baseSelect()
            ->select('pv.sku AS variant_sku, p.code AS product_code')
            ->join('products p', 'p.id = pi.product_id', 'left')
            ->groupStart()
                ->like('pv.sku', $filters['sku'])
                ->orLike('p.code', $filters['sku'])
            ->groupEnd();

        $total = $builder->countAllResults(false);

        $data = $builder->orderBy('pi.created_at', 'DESC')
            ->limit($filters['limit'], $filters['offset'])
            ->get()
            ->getResultArray();

        return ['data' => $data, 'total' => $total];
    }

    private function baseSelect()
    {
        return $this->db->table('product_images pi')
            ->select('pi.*, pv.product_id AS variant_product_id')
            ->join('product_variants_v2 pv', 'pv.id = pi.variant_id', 'left')
            ->where('pi.deleted_at', null);
    }

    private function applyYearFilter($builder, int $year): void
    {
        $driver = strtolower($this->db->DBDriver);
        if ($driver === 'sqlite3') {
            $builder->where("strftime('%Y', pi.created_at)", (string) $year);
        } else {
            $builder->where('YEAR(pi.created_at)', $year);
        }
    }

    private function applyMonthFilter($builder, int $month): void
    {
        $driver = strtolower($this->db->DBDriver);
        if ($driver === 'sqlite3') {
            $builder->where("strftime('%m', pi.created_at)", str_pad((string) $month, 2, '0', STR_PAD_LEFT));
        } else {
            $builder->where('MONTH(pi.created_at)', $month);
        }
    }
}
