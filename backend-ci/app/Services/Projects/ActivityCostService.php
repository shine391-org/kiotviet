<?php

namespace App\Services\Projects;

use App\Repositories\Projects\ActivityTypeRepository;

/**
 * Activity cost helper (stub for future billing).
 *
 * @agent-service: Activity cost
 * @agent-pattern: Thin wrapper
 * @agent-reusable: LOW
 */
class ActivityCostService
{
    protected ActivityTypeRepository $activities;

    public function __construct(?ActivityTypeRepository $activities = null)
    {
        $this->activities = $activities ?? new ActivityTypeRepository();
    }

    /** @agent-use: Seed or fetch activity type rate */
    public function create(array $data): array
    {
        return $this->activities->create($data);
    }

    public function find(int $id): ?array
    {
        return $this->activities->find($id);
    }
}
