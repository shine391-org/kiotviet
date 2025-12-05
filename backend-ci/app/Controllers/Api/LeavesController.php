<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\HR\LeaveService;
use CodeIgniter\API\ResponseTrait;

/**
 * Leaves API.
 *
 * @agent-controller: Leaves
 * @agent-pattern: Thin controller
 */
class LeavesController extends BaseController
{
    use ResponseTrait;

    protected LeaveService $service;

    public function __construct()
    {
        $this->service = service('leaveService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function apply()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->apply($payload)));
    }

    public function approve($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->approve((int) $id, $payload)));
    }

    public function balance()
    {
        $employeeId = (int) ($this->request->getGet('employee_id') ?? 0);
        $leaveTypeId = (int) ($this->request->getGet('leave_type_id') ?? 0);
        return $this->wrap(fn () => $this->respond([
            'success' => true,
            'data' => ['balance' => $this->service->balance($employeeId, $leaveTypeId)],
        ]));
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
