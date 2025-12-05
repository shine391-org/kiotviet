<?php

namespace App\Repositories\Contracts;

use App\Models\ContractModel;
use App\Models\ContractTermModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Contracts
 * @agent-pattern: Repository with terms
 * @agent-reusable: MEDIUM
 */
class ContractRepository
{
    protected ContractModel $contracts;
    protected ContractTermModel $terms;
    protected BaseConnection $db;

    public function __construct(?ContractModel $contracts = null, ?ContractTermModel $terms = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->contracts = $contracts ?? new ContractModel();
        $this->terms = $terms ?? new ContractTermModel();
    }

    public function create(array $data, array $terms): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->contracts->insert($payload);
        $id = (int) $this->contracts->getInsertID();
        $rows = [];
        foreach ($terms as $term) {
            $rows[] = [
                'contract_id' => $id,
                'description' => $term['description'] ?? null,
                'is_completed' => $term['is_completed'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->terms->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id) ?? ($payload + ['id' => $id, 'terms' => $rows]);
    }

    public function findById(int $id): ?array
    {
        $contract = $this->contracts->find($id);
        if (! $contract) { return null; }
        $terms = $this->terms->where('contract_id', $id)->get()->getResultArray();
        $contract = $this->hydrate($contract);
        $contract['terms'] = array_map(fn ($t) => $this->hydrateTerm($t), $terms);
        return $contract;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->contracts->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['template_id'] = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $row['value'] = isset($row['value']) ? (float) $row['value'] : 0.0;
        $row['auto_renew'] = isset($row['auto_renew']) ? (bool) $row['auto_renew'] : false;
        return $row;
    }

    private function hydrateTerm(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['contract_id'] = isset($row['contract_id']) ? (int) $row['contract_id'] : null;
        $row['is_completed'] = isset($row['is_completed']) ? (bool) $row['is_completed'] : false;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
