<?php

namespace App\Repositories\Contracts;

use App\Models\ContractTemplateModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Contract templates
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ContractTemplateRepository
{
    protected ContractTemplateModel $templates;
    protected BaseConnection $db;

    public function __construct(?ContractTemplateModel $templates = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->templates = $templates ?? new ContractTemplateModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->templates->insert($payload);
        $payload['id'] = (int) $this->templates->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->templates->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
