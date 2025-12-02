<?php

namespace App\Repositories\Accounting;

use App\Models\GLEntryModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: GL entries
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class GLEntryRepository
{
    protected GLEntryModel $model;
    protected BaseConnection $db;

    public function __construct(?GLEntryModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new GLEntryModel();
    }

    /**
     * Insert multiple entries in one transaction.
     *
     * @param array<int,array<string,mixed>> $entries
     */
    public function createBatch(array $entries): array
    {
        $now = $this->now();
        $payloads = [];
        foreach ($entries as $entry) {
            $payloads[] = $entry + ['created_at' => $now, 'updated_at' => $now];
        }

        $this->db->transStart();
        $this->model->insertBatch($payloads);
        $this->db->transComplete();

        return $this->listByFilters([]);
    }

    public function listByFilters(array $filters): array
    {
        $builder = $this->db->table('gl_entries');
        if (! empty($filters['account_id'])) {
            $builder->where('account_id', (int) $filters['account_id']);
        }
        if (! empty($filters['party_type'])) {
            $builder->where('party_type', $filters['party_type']);
        }
        if (! empty($filters['party_id'])) {
            $builder->where('party_id', (int) $filters['party_id']);
        }
        if (! empty($filters['from_date'])) {
            $builder->where('posting_date >=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $builder->where('posting_date <=', $filters['to_date']);
        }
        $rows = $builder->orderBy('posting_date', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['account_id'] = isset($row['account_id']) ? (int) $row['account_id'] : null;
        $row['party_id'] = isset($row['party_id']) ? (int) $row['party_id'] : null;
        $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
        $row['debit'] = isset($row['debit']) ? (float) $row['debit'] : 0.0;
        $row['credit'] = isset($row['credit']) ? (float) $row['credit'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
