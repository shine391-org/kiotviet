<?php

namespace App\Repositories\Assets;

use App\Models\AssetModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Asset persistence.
 *
 * @agent-repository: Asset
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class AssetRepository
{
    protected AssetModel $assets;
    protected BaseConnection $db;

    public function __construct(?AssetModel $assets = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->ensureTable();
        $this->assets = $assets ?? new AssetModel();
    }

    public function nextNumber(): string
    {
        $prefix = 'AST-' . date('Ymd');
        $count = $this->assets->where('asset_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->assets->insert($payload);
        $payload['id'] = (int) $this->assets->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->assets->update($id, $data + ['updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->assets->find($id);
        return $row ?: null;
    }

    public function list(array $filters = []): array
    {
        $b = $this->assets->builder();
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['category'])) {
            $b->where('category', $filters['category']);
        }
        if (! empty($filters['search'])) {
            $b->like('asset_name', $filters['search'])->orLike('asset_number', $filters['search']);
        }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function ensureTable(): void
    {
        if ($this->db->tableExists('assets') || ENVIRONMENT !== 'testing') {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS assets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            asset_number VARCHAR(100) NULL,
            asset_name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NULL,
            purchase_date DATE NULL,
            cost DECIMAL(14,2) DEFAULT 0,
            location VARCHAR(255) NULL,
            status VARCHAR(50) DEFAULT 'draft',
            salvage_value DECIMAL(14,2) DEFAULT 0,
            useful_life_months INT DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
