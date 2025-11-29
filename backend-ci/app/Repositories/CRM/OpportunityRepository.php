<?php

namespace App\Repositories\CRM;

use App\Models\OpportunityItemModel;
use App\Models\OpportunityModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Opportunities
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class OpportunityRepository
{
    protected OpportunityModel $opportunities;
    protected OpportunityItemModel $items;
    protected BaseConnection $db;

    public function __construct(?OpportunityModel $opportunities = null, ?OpportunityItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->opportunities = $opportunities ?? new OpportunityModel();
        $this->items = $items ?? new OpportunityItemModel();
    }

    public function create(array $data, array $items): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->opportunities->insert($payload);
        $id = (int) $this->opportunities->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'opportunity_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->items->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id) ?? ($payload + ['id' => $id, 'items' => $rows]);
    }

    public function findById(int $id): ?array
    {
        $opp = $this->opportunities->find($id);
        if (! $opp) { return null; }
        $items = $this->items->where('opportunity_id', $id)->get()->getResultArray();
        $opp = $this->hydrate($opp);
        $opp['items'] = array_map(fn ($row) => $this->hydrateItem($row), $items);
        return $opp;
    }

    public function updateOpportunity(int $id, array $data): bool
    {
        return (bool) $this->opportunities->update($id, $data + ['updated_at' => $this->now()]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['lead_id'] = isset($row['lead_id']) ? (int) $row['lead_id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['probability'] = isset($row['probability']) ? (int) $row['probability'] : 0;
        $row['expected_value'] = isset($row['expected_value']) ? (float) $row['expected_value'] : 0.0;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['opportunity_id'] = isset($row['opportunity_id']) ? (int) $row['opportunity_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0;
        $row['price'] = isset($row['price']) ? (float) $row['price'] : 0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
