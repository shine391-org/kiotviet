<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\InventoryService;
use CodeIgniter\API\ResponseTrait;

/** Inventory API. @agent-controller: Inventory @agent-pattern: Thin controller - routing only */
class InventoryController extends BaseController
{
    use ResponseTrait;

    protected InventoryService $service;
    public function __construct() { $this->service = service('inventoryService'); }

    /** List warehouses. @agent-use: GET /api/warehouses @agent-pattern: Standard list */
    public function warehouses() { return $this->wrap(fn () => $this->respond($this->service->listWarehouses($this->request->getGet()))); }

    /** Show warehouse. @agent-use: GET /api/warehouses/{id} @agent-pattern: Get by id */
    public function showWarehouse($id) { return $this->wrap(fn () => $this->respond($this->service->showWarehouse((int) $id))); }

    /** Create warehouse. @agent-use: POST /api/warehouses @agent-pattern: Thin create */
    public function createWarehouse() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->createWarehouse($data))); }

    /** Update warehouse. @agent-use: PUT /api/warehouses/{id} @agent-pattern: Thin update */
    public function updateWarehouse($id) { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->updateWarehouse((int) $id, $data))); }

    /** Delete warehouse. @agent-use: DELETE /api/warehouses/{id} @agent-pattern: Thin delete */
    public function deleteWarehouse($id) { return $this->wrap(fn () => $this->respond($this->service->deleteWarehouse((int) $id))); }

    /** List movements. @agent-use: GET /api/inventory/movements @agent-pattern: List */
    public function movements() { return $this->wrap(fn () => $this->respond($this->service->movements($this->request->getGet()))); }

    /** Create movement. @agent-use: POST /api/inventory/movements @agent-pattern: Thin create */
    public function createMovement() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->createMovement($data))); }

    /** Valuation list. @agent-use: GET /api/inventory/valuation @agent-pattern: Delegate list */
    public function valuations() { return $this->wrap(fn () => $this->respond($this->service->valuations($this->request->getGet()))); }

    /** Alerts list. @agent-use: GET /api/inventory/alerts */
    public function alerts() { return $this->wrap(fn () => $this->respond($this->service->alerts($this->request->getGet()))); }

    /** Resolve alert. @agent-use: PUT /api/inventory/alerts/{id}/resolve */
    public function resolveAlert($id) { $user = $this->request->getJSON(true)['resolved_by'] ?? null; return $this->wrap(fn () => $this->respond($this->service->resolveAlert((int) $id, $user ? (int) $user : null))); }

    /** Ignore alert. @agent-use: PUT /api/inventory/alerts/{id}/ignore */
    public function ignoreAlert($id) { $user = $this->request->getJSON(true)['resolved_by'] ?? null; return $this->wrap(fn () => $this->respond($this->service->ignoreAlert((int) $id, $user ? (int) $user : null))); }

    /** Reserve stock. @agent-use: POST /api/inventory/reserve */
    public function reserveStock() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->reserveStock($data))); }

    /** Release reserved stock. @agent-use: POST /api/inventory/release */
    public function releaseStock() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->releaseStock($data))); }

    /** Shared try/catch wrapper. */
    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
