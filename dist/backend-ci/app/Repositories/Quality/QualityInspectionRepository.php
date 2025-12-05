<?php

namespace App\Repositories\Quality;

use App\Models\QualityInspectionItemModel;
use App\Models\QualityInspectionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Quality inspection persistence.
 *
 * @agent-repository: Quality inspections
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class QualityInspectionRepository
{
    protected QualityInspectionModel $inspections;
    protected QualityInspectionItemModel $items;
    protected BaseConnection $db;

    public function __construct(
        ?QualityInspectionModel $inspections = null,
        ?QualityInspectionItemModel $items = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->inspections = $inspections ?? new QualityInspectionModel($this->db);
        $this->items = $items ?? new QualityInspectionItemModel($this->db);
    }

    /** List inspections by filters. @agent-use: GET /api/quality-inspections */
    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /** Find inspection only. */
    public function find(int $id): ?array
    {
        $row = $this->inspections->find($id);
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    /** Find inspection with items. @agent-pattern: Aggregate fetch */
    public function findWithItems(int $id): ?array
    {
        $inspection = $this->find($id);
        if (! $inspection) {
            return null;
        }
        $inspection['items'] = $this->itemsByInspection($id);
        return $inspection;
    }

    /**
     * Create inspection with items.
     * @agent-use: Draft creation
     * @agent-pattern: Transactional insert
     */
    public function create(array $inspection, array $items): array
    {
        $now = $this->now();
        $payload = $inspection + ['created_at' => $now, 'updated_at' => $now];

        $this->db->transStart();
        $this->inspections->insert($payload);
        $id = (int) $this->inspections->getInsertID();
        if (! empty($items)) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'inspection_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $this->db->table('quality_inspection_items')->insertBatch($rows);
        }
        $this->db->transComplete();

        return $this->findWithItems($id) ?? [];
    }

    /** Update inspection fields and return fresh row. */
    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->inspections->update($id, $payload);
        return $this->findWithItems($id) ?? [];
    }

    /**
     * Update evaluation results and status in one transaction.
     * @agent-use: Submit/approve/reject flow
     * @agent-pattern: Transactional update
     */
    public function updateEvaluation(int $id, array $items, array $meta): array
    {
        $now = $this->now();
        $this->db->transStart();
        foreach ($items as $item) {
            if (empty($item['id'])) {
                continue;
            }
            $update = [
                'pass_flag' => $item['pass_flag'] ?? null,
                'value_numeric' => $item['value_numeric'] ?? null,
                'value_text' => $item['value_text'] ?? null,
                'updated_at' => $now,
            ];
            $this->db->table('quality_inspection_items')
                ->where('id', $item['id'])
                ->update($update);
        }
        $this->inspections->update($id, $meta + ['updated_at' => $now]);
        $this->db->transComplete();
        return $this->findWithItems($id) ?? [];
    }

    /** IDs of parameters linked to inspection. */
    public function parameterIds(int $inspectionId): array
    {
        $rows = $this->db->table('quality_inspection_items')
            ->select('parameter_id')
            ->where('inspection_id', $inspectionId)
            ->get()
            ->getResultArray();
        return array_map('intval', array_column($rows, 'parameter_id'));
    }

    /** Count items for parameter usage. */
    public function parameterUsageCount(int $parameterId): int
    {
        return (int) $this->db->table('quality_inspection_items')
            ->where('parameter_id', $parameterId)
            ->countAllResults();
    }

    /** List items for an inspection. @return array<int,array> */
    public function itemsByInspection(int $inspectionId): array
    {
        $result = $this->db->table('quality_inspection_items')
            ->where('inspection_id', $inspectionId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $result ?: [];
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    private function applyFilters(array $filters)
    {
        $b = $this->inspections->builder();
        if (! empty($filters['reference_type'])) {
            $b->where('reference_type', $filters['reference_type']);
        }
        if (! empty($filters['reference_id'])) {
            $b->where('reference_id', $filters['reference_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['result'])) {
            $b->where('result', $filters['result']);
        }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
