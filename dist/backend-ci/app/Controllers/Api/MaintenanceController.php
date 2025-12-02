<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Assets\MaintenanceService;
use CodeIgniter\API\ResponseTrait;

/**
 * Maintenance API.
 *
 * @agent-controller: Maintenance
 * @agent-pattern: Thin controller
 */
class MaintenanceController extends BaseController
{
    use ResponseTrait;

    protected MaintenanceService $service;

    public function __construct()
    {
        $this->service = service('maintenanceService');
    }

    public function createSchedule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createSchedule($payload)));
    }

    public function createWorkOrder()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createWorkOrder($payload)));
    }

    public function completeWorkOrder($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->completeWorkOrder((int) $id, $payload)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
