<?php

namespace App\Repositories\Taxes;

use App\Models\TaxTemplateModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Tax templates
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class TaxTemplateRepository
{
    protected TaxTemplateModel $templates;
    protected BaseConnection $db;

    public function __construct(?TaxTemplateModel $templates = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->templates = $templates ?? new TaxTemplateModel();
    }

    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->templates->insert($payload);
        $payload['id'] = (int) $this->templates->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->templates->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function allActive(): array
    {
        $rows = $this->templates->where('status', 'active')->get()->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function encode(array $data): array
    {
        if (isset($data['is_inclusive'])) {
            $data['is_inclusive'] = $data['is_inclusive'] ? 1 : 0;
        }
        return $data;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['rate_percent'] = isset($row['rate_percent']) ? (float) $row['rate_percent'] : 0.0;
        $row['is_inclusive'] = isset($row['is_inclusive']) ? (bool) $row['is_inclusive'] : false;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
