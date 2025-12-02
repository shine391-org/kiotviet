<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Dashboard\DashboardService;
use CodeIgniter\API\ResponseTrait;
/**
 * Dashboard endpoints (KPI, revenue, rankings, activities).
 *
 * @agent-controller: Dashboard
 * @agent-pattern: Thin controller
 * @agent-reusable: HIGH
 */
class DashboardController extends BaseController
{
    use ResponseTrait;

    protected DashboardService $service;

    public function __construct()
    {
        $this->service = service('dashboardService');
    }

    /**
     * @agent-use: GET /api/dashboard/kpi-today
     * @agent-pattern: KPI endpoint
     */
    public function kpiToday()
    {
        return $this->wrap(fn () => $this->respond($this->service->kpiToday($this->request->getGet())));
    }

    /**
     * @agent-use: GET /api/dashboard/revenue-chart
     * @agent-pattern: Revenue chart endpoint
     */
    public function revenueChart()
    {
        return $this->wrap(fn () => $this->respond($this->service->revenueChart($this->request->getGet())));
    }

    /**
     * @agent-use: GET /api/dashboard/top-products
     * @agent-pattern: Top products endpoint
     */
    public function topProducts()
    {
        return $this->wrap(fn () => $this->respond($this->service->topProducts($this->request->getGet())));
    }

    /**
     * @agent-use: GET /api/dashboard/top-customers
     * @agent-pattern: Top customers endpoint
     */
    public function topCustomers()
    {
        return $this->wrap(fn () => $this->respond($this->service->topCustomers($this->request->getGet())));
    }

    /**
     * @agent-use: GET /api/dashboard/activities
     * @agent-pattern: Activity feed endpoint
     */
    public function activities()
    {
        return $this->wrap(fn () => $this->respond($this->service->activities($this->request->getGet())));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
