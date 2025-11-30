<?php

namespace App\Repositories\Assets;

use App\Models\MaintenanceScheduleModel;
use App\Models\MaintenanceWorkOrderModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Maintenance repository.
 *
 * @agent-repository: Maintenance
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class MaintenanceRepository
{
    protected MaintenanceScheduleModel $schedules;
    protected MaintenanceWorkOrderModel $workOrders;
    protected BaseConnection $db;

    public function __construct(
        ?MaintenanceScheduleModel $schedules = null,
        ?MaintenanceWorkOrderModel $workOrders = null,
        ?BaseConnection $db = null
    ) {
        $this->schedules = $schedules ?? new MaintenanceScheduleModel();
        $this->workOrders = $workOrders ?? new MaintenanceWorkOrderModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function createSchedule(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->schedules->insert($payload);
        $payload['id'] = (int) $this->schedules->getInsertID();
        return $payload;
    }

    public function findSchedule(int $id): ?array
    {
        $row = $this->schedules->find($id);
        return $row ?: null;
    }

    public function createWorkOrder(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->workOrders->insert($payload);
        $payload['id'] = (int) $this->workOrders->getInsertID();
        return $payload;
    }

    public function updateWorkOrderStatus(int $id, string $status, array $extra = []): void
    {
        $this->workOrders->update($id, $extra + ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function findWorkOrder(int $id): ?array
    {
        $row = $this->workOrders->find($id);
        return $row ?: null;
    }

    public function nextWorkOrderNumber(): string
    {
        $prefix = 'MWO-' . date('Ymd');
        $count = $this->workOrders->where('work_order_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
