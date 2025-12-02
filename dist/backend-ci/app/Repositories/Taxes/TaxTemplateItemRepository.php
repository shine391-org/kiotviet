<?php

namespace App\Repositories\Taxes;

use App\Models\TaxTemplateItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Tax template items
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class TaxTemplateItemRepository
{
    protected TaxTemplateItemModel $items;
    protected BaseConnection $db;

    public function __construct(?TaxTemplateItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->items = $items ?? new TaxTemplateItemModel();
    }

    public function saveItems(int $templateId, array $items): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'template_id' => $templateId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->items->insertBatch($rows);
        }
    }

    public function findByTemplate(int $templateId): array
    {
        $rows = $this->items->where('template_id', $templateId)->findAll();
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
