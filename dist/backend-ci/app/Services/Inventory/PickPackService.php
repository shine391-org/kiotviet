<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\PickListRepository;
use App\Repositories\Inventory\PackingSlipRepository;
use App\Repositories\Inventory\StockEntryRepository;
use App\Validators\PickListValidator;
use App\Validators\PackingSlipValidator;
use RuntimeException;

/**
 * Pick + pack flow service.
 *
 * @agent-service: Pick/Pack
 * @agent-pattern: Orchestrator
 * @agent-reusable: MEDIUM
 */
class PickPackService
{
    protected PickListRepository $pickLists;
    protected PackingSlipRepository $packingSlips;
    protected StockEntryRepository $stockEntries;
    protected PickListValidator $pickValidator;
    protected PackingSlipValidator $packingValidator;

    public function __construct(
        ?PickListRepository $pickLists = null,
        ?PackingSlipRepository $packingSlips = null,
        ?StockEntryRepository $stockEntries = null,
        ?PickListValidator $pickValidator = null,
        ?PackingSlipValidator $packingValidator = null
    ) {
        $this->pickLists = $pickLists ?? new PickListRepository();
        $this->packingSlips = $packingSlips ?? new PackingSlipRepository();
        $this->stockEntries = $stockEntries ?? new StockEntryRepository();
        $this->pickValidator = $pickValidator ?? new PickListValidator();
        $this->packingValidator = $packingValidator ?? new PackingSlipValidator();
    }

    /**
     * Create pick list.
     *
     * @agent-use: POST /api/pick-lists
     * @agent-pattern: Create with items
     */
    public function createPickList(array $input): array
    {
        $data = $this->pickValidator->validateCreate($input);
        if (! empty($data['stock_entry_id'])) {
            $this->ensureEntryExists((int) $data['stock_entry_id']);
        }
        $payload = [
            'pick_list_number' => $this->pickLists->nextNumber(),
            'stock_entry_id' => $data['stock_entry_id'] ?? null,
            'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
            'status' => 'open',
            'created_by' => $data['created_by'] ?? null,
        ];
        $created = $this->pickLists->create($payload, $data['items']);
        return ['success' => true, 'data' => $created];
    }

    /** Show pick list detail. */
    public function showPickList(int $id): array
    {
        return ['success' => true, 'data' => $this->requirePickList($id)];
    }

    /**
     * Create packing slip from pick or stock entry.
     *
     * @agent-use: POST /api/packing-slips
     * @agent-pattern: Create with items
     */
    public function createPackingSlip(array $input): array
    {
        $data = $this->packingValidator->validateCreate($input);
        if (! empty($data['pick_list_id'])) {
            $this->requirePickList((int) $data['pick_list_id']);
        }
        if (! empty($data['stock_entry_id'])) {
            $this->ensureEntryExists((int) $data['stock_entry_id']);
        }
        $payload = [
            'packing_slip_number' => $this->packingSlips->nextNumber(),
            'pick_list_id' => $data['pick_list_id'] ?? null,
            'stock_entry_id' => $data['stock_entry_id'] ?? null,
            'source_warehouse_id' => $data['source_warehouse_id'] ?? null,
            'target_warehouse_id' => $data['target_warehouse_id'] ?? null,
            'status' => 'packed',
            'created_by' => $data['created_by'] ?? null,
        ];
        $created = $this->packingSlips->create($payload, $data['items']);
        return ['success' => true, 'data' => $created];
    }

    /** Show packing slip detail. */
    public function showPackingSlip(int $id): array
    {
        $slip = $this->packingSlips->findById($id);
        if (! $slip) {
            throw new RuntimeException('Packing slip not found');
        }
        return ['success' => true, 'data' => $slip];
    }

    private function ensureEntryExists(int $id): void
    {
        if (! $this->stockEntries->findById($id)) {
            throw new RuntimeException('Stock entry not found');
        }
    }

    private function requirePickList(int $id): array
    {
        $row = $this->pickLists->findById($id);
        if (! $row) {
            throw new RuntimeException('Pick list not found');
        }
        return $row;
    }
}
