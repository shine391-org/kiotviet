<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\HR\AttendanceService;
use CodeIgniter\API\ResponseTrait;

/**
 * Attendance API.
 *
 * @agent-controller: Attendances
 * @agent-pattern: Thin controller
 */
class AttendancesController extends BaseController
{
    use ResponseTrait;

    protected AttendanceService $service;

    public function __construct()
    {
        $this->service = service('attendanceService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->log($payload)));
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
