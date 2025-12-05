<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\HR\PayrollService;
use CodeIgniter\API\ResponseTrait;

/**
 * Payroll API.
 *
 * @agent-controller: Payroll
 * @agent-pattern: Thin controller
 */
class PayrollController extends BaseController
{
    use ResponseTrait;

    protected PayrollService $service;

    public function __construct()
    {
        $this->service = service('payrollService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->runPayroll($payload)));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
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
