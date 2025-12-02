<?php

namespace App\Repositories\Inventory;

use App\Models\StockLedgerModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Stock ledger repository.
 *
 * @agent-repository: Stock ledger
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class StockLedgerRepository
{
    protected StockLedgerModel $ledger;
    protected BaseConnection $db;

    public function __construct(?StockLedgerModel $ledger = null, ?BaseConnection $db = null)
    {
        $this->ledger = $ledger ?? new StockLedgerModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function findByReference(string $type, int $refId, int $seq = 1): ?array
    {
        $row = $this->ledger->where('reference_type', $type)
            ->where('reference_id', $refId)
            ->where('reference_seq', $seq)
            ->first();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s')];
        $this->ledger->insert($payload);
        $payload['id'] = (int) $this->ledger->getInsertID();
        return $payload;
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('stock_ledgers');
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        if (! empty($filters['branch_id'])) { $b->where('branch_id', $filters['branch_id']); }
        if (! empty($filters['batch_id'])) { $b->where('batch_id', $filters['batch_id']); }
        if (! empty($filters['reference_type'])) { $b->where('reference_type', $filters['reference_type']); }
        return $b->orderBy('movement_date', 'DESC')->limit(200)->get()->getResultArray();
    }
}
