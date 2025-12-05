<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Reports\ReportService;
use CodeIgniter\API\ResponseTrait;

/**
 * Reports API (financial & stock).
 *
 * @agent-controller: Reports
 * @agent-pattern: Thin controller
 */
class ReportsController extends BaseController
{
    use ResponseTrait;

    protected ReportService $service;

    public function __construct()
    {
        $this->service = service('reportService');
    }

    public function gl()
    {
        return $this->wrap(fn () => $this->respond($this->service->gl($this->request->getGet())));
    }

    public function profitLoss()
    {
        return $this->wrap(fn () => $this->respond($this->service->profitLoss($this->request->getGet())));
    }

    public function balanceSheet()
    {
        return $this->wrap(fn () => $this->respond($this->service->balanceSheet($this->request->getGet())));
    }

    public function aging()
    {
        return $this->wrap(fn () => $this->respond($this->service->aging($this->request->getGet())));
    }

    public function stockBalance()
    {
        return $this->wrap(fn () => $this->respond($this->service->stockBalance($this->request->getGet())));
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
