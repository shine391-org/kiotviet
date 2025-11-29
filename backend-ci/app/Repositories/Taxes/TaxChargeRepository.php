<?php

namespace App\Repositories\Taxes;

use App\Models\TaxChargeModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Tax charges
 * @agent-pattern: Repository pattern
 * @agent-reusable: LOW
 */
class TaxChargeRepository
{
    protected TaxChargeModel $charges;
    protected BaseConnection $db;

    public function __construct(?TaxChargeModel $charges = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->charges = $charges ?? new TaxChargeModel();
    }

    public function listByTemplate(int $templateId): array
    {
        $rows = $this->charges->where('template_id', $templateId)->get()->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['template_id'] = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $row['rate_percent'] = isset($row['rate_percent']) ? (float) $row['rate_percent'] : 0.0;
        return $row;
    }
}
