<?php

namespace App\Repositories\Inventory;

use App\Models\StockReconciliationModel;
use App\Models\StockReconciliationItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Stock reconciliation repository.
 *
 * @agent-repository: Stock reconciliation
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class StockReconciliationRepository
{
    protected StockReconciliationModel $recon;
    protected StockReconciliationItemModel $items;
    protected BaseConnection $db;

    public function __construct(?StockReconciliationModel $recon = null, ?StockReconciliationItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->recon = $recon ?? new StockReconciliationModel();
        $this->items = $items ?? new StockReconciliationItemModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function create(array $data, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->db->table('stock_reconciliations')->insert($payload);
        $id = (int) $this->db->insertID();
        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'reconciliation_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('stock_reconciliation_items')->insertBatch($rows);
        }
        $this->db->transComplete();
        return $payload + ['id' => $id];
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('stock_reconciliations');
        if (! empty($filters['branch_id'])) { $b->where('branch_id', $filters['branch_id']); }
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['search'])) { $b->like('recon_number', $filters['search']); }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function find(int $id): ?array
    {
        $row = $this->recon->find($id);
        if (! $row) { return null; }
        $items = $this->items->where('reconciliation_id', $id)->findAll();
        $row['items'] = $items;
        return $row;
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $payload = $extra + ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        $this->db->table('stock_reconciliations')->where('id', $id)->update($payload);
    }

    public function updateItemVariance(int $id, float $current, float $variance): void
    {
        $this->db->table('stock_reconciliation_items')->where('id', $id)->update([
            'current_qty' => $current,
            'variance_qty' => $variance,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Expose DB connection for transactional operations.
     */
    public function db(): BaseConnection
    {
        return $this->db;
    }
}
