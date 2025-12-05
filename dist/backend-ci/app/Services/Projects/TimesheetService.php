<?php

namespace App\Services\Projects;

use App\Repositories\Projects\TimesheetRepository;
use App\Repositories\Projects\ActivityTypeRepository;
use App\Repositories\Projects\TaskRepository;
use App\Validators\TimesheetValidator;
use RuntimeException;

/**
 * Timesheet business logic.
 *
 * @agent-service: Timesheet
 * @agent-pattern: Create + totals + submit
 * @agent-reusable: MEDIUM
 */
class TimesheetService
{
    protected TimesheetRepository $timesheets;
    protected ActivityTypeRepository $activities;
    protected TaskRepository $tasks;
    protected TimesheetValidator $validator;

    public function __construct(
        ?TimesheetRepository $timesheets = null,
        ?ActivityTypeRepository $activities = null,
        ?TaskRepository $tasks = null,
        ?TimesheetValidator $validator = null
    ) {
        $this->timesheets = $timesheets ?? new TimesheetRepository();
        $this->activities = $activities ?? new ActivityTypeRepository();
        $this->tasks = $tasks ?? new TaskRepository();
        $this->validator = $validator ?? new TimesheetValidator();
    }

    /** @agent-use: GET /api/timesheets @agent-pattern: List with filters */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->timesheets->list($filters)];
    }

    /** @agent-use: GET /api/timesheets/{id} @agent-pattern: Get by id */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: POST /api/timesheets @agent-pattern: Create + totals */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $number = $this->timesheets->nextNumber();
        $header = [
            'timesheet_number' => $number,
            'project_id' => $data['project_id'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ];
        $items = $this->applyRates($data['items']);
        [$hours, $billable, $cost] = $this->totals($items);
        $header['total_hours'] = $hours;
        $header['total_billable'] = $billable;
        $header['total_cost'] = $cost;

        $created = $this->timesheets->create($header, $items);
        $this->updateTaskActuals($items);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: POST /api/timesheets/{id}/submit @agent-pattern: Status transition */
    public function submit(int $id, array $input): array
    {
        $data = $this->validator->validateSubmit($input);
        $ts = $this->require($id);
        if ($ts['status'] === 'submitted') {
            return ['success' => true, 'data' => $ts];
        }
        $this->timesheets->updateStatus($id, 'submitted', [
            'submitted_by' => $data['submitted_by'] ?? null,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: GET /api/projects/{id}/timesheets/summary @agent-pattern: Aggregate */
    public function summaryByProject(int $projectId): array
    {
        $rows = $this->timesheets->list(['project_id' => $projectId]);
        $hours = array_sum(array_map(fn ($r) => (float) ($r['total_hours'] ?? 0), $rows));
        $billable = array_sum(array_map(fn ($r) => (float) ($r['total_billable'] ?? 0), $rows));
        $cost = array_sum(array_map(fn ($r) => (float) ($r['total_cost'] ?? 0), $rows));
        return ['success' => true, 'data' => [
            'project_id' => $projectId,
            'total_hours' => $hours,
            'total_billable' => $billable,
            'total_cost' => $cost,
        ]];
    }

    private function applyRates(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $billingRate = $item['billing_rate'] ?? null;
            $costRate = $item['cost_rate'] ?? null;
            if ($billingRate === null && ! empty($item['activity_type_id'])) {
                $activity = $this->activities->find((int) $item['activity_type_id']);
                if ($activity) {
                    $billingRate = (float) ($activity['billing_rate'] ?? 0);
                    $costRate = $costRate ?? (float) ($activity['cost_rate'] ?? 0);
                }
            }
            $billingRate = $billingRate ?? 0.0;
            $costRate = $costRate ?? 0.0;
            $hours = (float) $item['hours'];
            $result[] = $item + [
                'billing_rate' => $billingRate,
                'cost_rate' => $costRate,
                'billable_amount' => round($hours * $billingRate, 2),
                'cost_amount' => round($hours * $costRate, 2),
            ];
        }
        return $result;
    }

    /**
     * @return array{0:float,1:float,2:float}
     */
    private function totals(array $items): array
    {
        $hours = array_sum(array_column($items, 'hours'));
        $billable = array_sum(array_column($items, 'billable_amount'));
        $cost = array_sum(array_column($items, 'cost_amount'));
        return [$hours, $billable, $cost];
    }

    private function updateTaskActuals(array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['task_id'])) {
                continue;
            }
            $task = $this->tasks->find((int) $item['task_id']);
            if (! $task) {
                continue;
            }
            $newActual = (float) ($task['actual_hours'] ?? 0) + (float) $item['hours'];
            $this->tasks->update((int) $item['task_id'], ['actual_hours' => $newActual]);
        }
    }

    private function require(int $id): array
    {
        $row = $this->timesheets->findById($id);
        if (! $row) {
            throw new RuntimeException('Timesheet not found');
        }
        return $row;
    }
}
