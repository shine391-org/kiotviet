<?php

namespace App\Services\Assets;

use App\Repositories\Assets\MaintenanceRepository;
use App\Repositories\Assets\AssetRepository;
use App\Services\Inventory\StockEntryService;
use App\Validators\MaintenanceValidator;
use RuntimeException;

/**
 * Maintenance scheduling and work orders.
 *
 * @agent-service: Maintenance
 * @agent-pattern: Schedule + WO completion
 * @agent-reusable: MEDIUM
 */
class MaintenanceService
{
    protected MaintenanceRepository $repo;
    protected AssetRepository $assets;
    protected MaintenanceValidator $validator;
    protected StockEntryService $stockEntries;

    public function __construct(
        ?MaintenanceRepository $repo = null,
        ?AssetRepository $assets = null,
        ?MaintenanceValidator $validator = null,
        ?StockEntryService $stockEntries = null
    ) {
        $this->repo = $repo ?? new MaintenanceRepository();
        $this->assets = $assets ?? new AssetRepository();
        $this->validator = $validator ?? new MaintenanceValidator();
        $this->stockEntries = $stockEntries ?? new StockEntryService();
    }

    /** @agent-use: POST /api/maintenance-schedules */
    public function createSchedule(array $input): array
    {
        $data = $this->validator->validateSchedule($input);
        $this->requireAsset((int) $data['asset_id']);
        $created = $this->repo->createSchedule($data);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: POST /api/maintenance-work-orders */
    public function createWorkOrder(array $input): array
    {
        $data = $this->validator->validateWorkOrderCreate($input);
        $this->requireAsset((int) $data['asset_id']);
        if (! empty($data['schedule_id'])) {
            $this->requireSchedule((int) $data['schedule_id']);
        }
        $wo = [
            'work_order_number' => $this->repo->nextWorkOrderNumber(),
            'asset_id' => $data['asset_id'],
            'schedule_id' => $data['schedule_id'] ?? null,
            'status' => 'open',
            'description' => $data['description'] ?? null,
            'planned_date' => $data['planned_date'] ?? null,
        ];
        $created = $this->repo->createWorkOrder($wo);
        $this->assets->update($data['asset_id'], ['status' => 'maintenance']);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: POST /api/maintenance-work-orders/{id}/complete */
    public function completeWorkOrder(int $id, array $input): array
    {
        $wo = $this->requireWorkOrder($id);
        if (! in_array($wo['status'], ['open', 'in_progress'], true)) {
            throw new RuntimeException('Work order already completed');
        }
        $data = $this->validator->validateComplete($input);
        if (! empty($data['parts'])) {
            $this->issueParts($wo, $data['parts']);
        }
        $this->repo->updateWorkOrderStatus($id, 'completed', [
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->assets->update((int) $wo['asset_id'], ['status' => 'active']);
        return ['success' => true, 'data' => $this->requireWorkOrder($id)];
    }

    private function issueParts(array $wo, array $parts): void
    {
        $items = [];
        $branchId = null;
        $warehouseId = null;
        foreach ($parts as $part) {
            $items[] = [
                'product_id' => $part['product_id'],
                'qty' => $part['qty'],
                'source_warehouse_id' => $part['warehouse_id'] ?? null,
                'source_branch_id' => $part['branch_id'] ?? null,
            ];
            $branchId = $branchId ?? ($part['branch_id'] ?? null);
            $warehouseId = $warehouseId ?? ($part['warehouse_id'] ?? null);
        }
        $payload = [
            'type' => 'issue',
            'branch_id' => $branchId,
            'source_warehouse_id' => $warehouseId,
            'reference_type' => 'maintenance_work_order',
            'reference_id' => $wo['id'],
            'items' => $items,
        ];
        $entry = $this->stockEntries->create($payload)['data'];
        $this->stockEntries->submit((int) $entry['id']);
    }

    private function requireAsset(int $id): array
    {
        $row = $this->assets->find($id);
        if (! $row) {
            throw new RuntimeException('Asset not found');
        }
        return $row;
    }

    private function requireSchedule(int $id): array
    {
        $row = $this->repo->findSchedule($id);
        if (! $row) {
            throw new RuntimeException('Maintenance schedule not found');
        }
        return $row;
    }

    private function requireWorkOrder(int $id): array
    {
        $row = $this->repo->findWorkOrder($id);
        if (! $row) {
            throw new RuntimeException('Maintenance work order not found');
        }
        return $row;
    }
}
