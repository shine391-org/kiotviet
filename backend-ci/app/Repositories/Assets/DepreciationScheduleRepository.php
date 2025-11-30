<?php

namespace App\Repositories\Assets;

use App\Models\DepreciationScheduleModel;
use App\Models\DepreciationScheduleLineModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Depreciation schedule persistence.
 *
 * @agent-repository: Depreciation schedule
 * @agent-pattern: Repository with lines
 * @agent-reusable: MEDIUM
 */
class DepreciationScheduleRepository
{
    protected DepreciationScheduleModel $schedules;
    protected DepreciationScheduleLineModel $lines;
    protected BaseConnection $db;

    public function __construct(
        ?DepreciationScheduleModel $schedules = null,
        ?DepreciationScheduleLineModel $lines = null,
        ?BaseConnection $db = null
    ) {
        $this->schedules = $schedules ?? new DepreciationScheduleModel();
        $this->lines = $lines ?? new DepreciationScheduleLineModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $schedule, array $lines): array
    {
        $now = $this->now();
        $payload = $schedule + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->schedules->insert($payload);
        $scheduleId = (int) $this->schedules->getInsertID();
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = $line + [
                'schedule_id' => $scheduleId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->lines->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($scheduleId);
    }

    public function findById(int $id): ?array
    {
        $row = $this->schedules->find($id);
        if (! $row) {
            return null;
        }
        $lines = $this->lines->where('schedule_id', $id)->orderBy('period_no', 'ASC')->findAll();
        $row['lines'] = array_map([$this, 'hydrateLine'], $lines);
        return $row;
    }

    public function findLine(int $scheduleId, int $periodNo): ?array
    {
        $row = $this->lines->where('schedule_id', $scheduleId)->where('period_no', $periodNo)->first();
        return $row ?: null;
    }

    public function markPosted(int $lineId, int $glEntryId): void
    {
        $this->lines->update($lineId, [
            'posted_gl_entry_id' => $glEntryId,
            'updated_at' => $this->now(),
        ]);
    }

    private function hydrateLine(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['schedule_id'] = isset($row['schedule_id']) ? (int) $row['schedule_id'] : null;
        $row['period_no'] = isset($row['period_no']) ? (int) $row['period_no'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        $row['posted_gl_entry_id'] = isset($row['posted_gl_entry_id']) ? (int) $row['posted_gl_entry_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
