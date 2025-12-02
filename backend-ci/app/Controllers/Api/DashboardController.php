<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Dashboard\DashboardService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

/**
 * Dashboard Controller - API endpoints for dashboard data
 * 
 * @agent-controller: Dashboard
 * @agent-pattern: Thin controller - routing only
 * @agent-reusable: HIGH
 */
class DashboardController extends BaseController
{
    use ResponseTrait;

    protected DashboardService $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    /**
     * Get today's KPI metrics
     * GET /api/dashboard/kpi-today
     * @agent-method: Standard response pattern
     */
    public function kpiToday(): ResponseInterface
    {
        try {
            $branchId = $this->request->getGet('branch_id');
            $data = $this->service->getKpiToday($branchId);
            
            return $this->respond([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard KPI error: ' . $e->getMessage());
            return $this->failServerError('Không thể tải dữ liệu KPI');
        }
    }

    /**
     * Get revenue chart data
     * GET /api/dashboard/revenue-chart?period=day&range=month&branch_id=1
     * @agent-pattern: REUSE for all chart endpoints
     */
    public function revenueChart(): ResponseInterface
    {
        try {
            $filters = [
                'period' => $this->request->getGet('period') ?? 'day',
                'range' => $this->request->getGet('range') ?? 'month',
                'branch_id' => $this->request->getGet('branch_id'),
                'chart_type' => $this->request->getGet('chart_type') ?? 'column',
            ];
            
            $data = $this->service->getRevenueChart($filters);
            
            return $this->respond([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard revenue chart error: ' . $e->getMessage());
            return $this->failServerError('Không thể tải biểu đồ doanh thu');
        }
    }

    /**
     * Get top products ranking
     * GET /api/dashboard/top-products?metric=net_revenue&range=month&limit=10
     * @agent-pattern: Standard list pattern
     */
    public function topProducts(): ResponseInterface
    {
        try {
            $filters = [
                'metric' => $this->request->getGet('metric') ?? 'net_revenue',
                'range' => $this->request->getGet('range') ?? 'month',
                'limit' => (int)($this->request->getGet('limit') ?? 10),
            ];
            
            $data = $this->service->getTopProducts($filters);
            
            return $this->respond([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard top products error: ' . $e->getMessage());
            return $this->failServerError('Không thể tải top sản phẩm');
        }
    }

    /**
     * Get top customers ranking
     * GET /api/dashboard/top-customers?range=month&limit=10
     * @agent-pattern: Standard list pattern
     */
    public function topCustomers(): ResponseInterface
    {
        try {
            $filters = [
                'range' => $this->request->getGet('range') ?? 'month',
                'limit' => (int)($this->request->getGet('limit') ?? 10),
            ];
            
            $data = $this->service->getTopCustomers($filters);
            
            return $this->respond([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard top customers error: ' . $e->getMessage());
            return $this->failServerError('Không thể tải top khách hàng');
        }
    }

    /**
     * Get recent activities timeline
     * GET /api/dashboard/activities?limit=15
     * @agent-pattern: Activity feed pattern
     */
    public function activities(): ResponseInterface
    {
        try {
            $filters = [
                'limit' => (int)($this->request->getGet('limit') ?? 15),
            ];
            
            $data = $this->service->getActivities($filters);
            
            return $this->respond([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Dashboard activities error: ' . $e->getMessage());
            return $this->failServerError('Không thể tải hoạt động gần đây');
        }
    }
}
