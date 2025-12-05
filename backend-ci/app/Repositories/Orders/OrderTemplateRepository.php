<?php

namespace App\Repositories\Orders;

use App\Models\OrderTemplateItemModel;
use App\Models\OrderTemplateModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Order template persistence.
 *
 * @agent-repository: Order templates
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class OrderTemplateRepository
{
    protected OrderTemplateModel $templates;
    protected OrderTemplateItemModel $items;
    protected BaseConnection $db;

    public function __construct(
        ?OrderTemplateModel $templates = null,
        ?OrderTemplateItemModel $items = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->templates = $templates ?? new OrderTemplateModel($this->db);
        $this->items = $items ?? new OrderTemplateItemModel($this->db);
    }

    /** List templates with filters. @agent-use: Template listing */
    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /** Count templates. */
    public function count(array $filters = []): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /** Find template by id with items. */
    public function findWithItems(int $id): ?array
    {
        $template = $this->templates->find($id);
        if (! $template) {
            return null;
        }
        $items = $this->items->where('template_id', $id)->orderBy('id', 'ASC')->findAll();
        $template['items'] = $items;
        return is_array($template) ? $template : (array) $template;
    }

    /** Create template with items. @agent-pattern: Transactional insert */
    public function create(array $template, array $items): array
    {
        $now = $this->now();
        $payload = $template + ['created_at' => $now, 'updated_at' => $now];

        $this->db->transStart();
        $this->templates->insert($payload);
        $templateId = (int) $this->templates->getInsertID();
        if (! empty($items)) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'template_id' => $templateId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('order_template_items')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $this->findWithItems($templateId) ?? [];
    }

    /** Update template and optionally replace items. */
    public function update(int $id, array $template, ?array $items = null): array
    {
        $payload = $template + ['updated_at' => $this->now()];
        $this->db->transStart();
        if (! empty($payload)) {
            $this->templates->update($id, $payload);
        }
        if ($items !== null) {
            $this->db->table('order_template_items')->where('template_id', $id)->delete();
            if (! empty($items)) {
                $rows = [];
                $now = $this->now();
                foreach ($items as $item) {
                    $rows[] = $item + [
                        'template_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $this->db->table('order_template_items')->insertBatch($rows);
            }
        }
        $this->db->transComplete();
        return $this->findWithItems($id) ?? [];
    }

    /** Delete template and items. */
    public function delete(int $id): void
    {
        $this->db->transStart();
        $this->db->table('order_template_items')->where('template_id', $id)->delete();
        $this->templates->delete($id);
        $this->db->transComplete();
    }

    private function applyFilters(array $filters)
    {
        $b = $this->templates->builder();
        if (! empty($filters['customer_id'])) {
            $b->where('customer_id', $filters['customer_id']);
        }
        if (array_key_exists('is_active', $filters)) {
            $b->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        if (! empty($filters['search'])) {
            $b->like('name', $filters['search']);
        }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** Check duplicate template name. */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $b = $this->templates->builder()->where('name', $name);
        if ($excludeId) {
            $b->where('id !=', $excludeId);
        }
        return $b->countAllResults() > 0;
    }
}
