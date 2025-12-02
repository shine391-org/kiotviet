<?php

namespace App\Repositories\Pricing;

use App\Models\PricingRuleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Pricing rule repository.
 *
 * @agent-repository: Pricing rules
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class PricingRuleRepository
{
    protected PricingRuleModel $model;
    protected BaseConnection $db;

    public function __construct(?PricingRuleModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new PricingRuleModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->model->update($id, $data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('pricing_rules');
        if (isset($filters['is_active'])) { $b->where('is_active', $filters['is_active']); }
        if (! empty($filters['customer_id'])) { $b->where('customer_id', $filters['customer_id']); }
        if (! empty($filters['project_id'])) { $b->where('project_id', $filters['project_id']); }
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        return $b->orderBy('priority', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function find(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ?: null;
    }
}
