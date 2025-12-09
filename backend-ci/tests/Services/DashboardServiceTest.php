<?php

namespace Tests\Services;

use App\Services\Dashboard\DashboardService;
use App\Repositories\Dashboard\DashboardRepository;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: DashboardService
 * @agent-pattern: Service test with stubbed repo
 */
class DashboardServiceTest extends CIUnitTestCase
{
    private DashboardService $service;
    private InMemoryDashboardRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryDashboardRepo();
        $this->service = new DashboardServiceWithRepo($this->repo);
    }

    public function testGetKpiTodayReturnsAllMetrics(): void
    {
        $result = $this->service->getKpiToday();

        $this->assertArrayHasKey('revenue', $result);
        $this->assertArrayHasKey('returns', $result);
        $this->assertArrayHasKey('netRevenue', $result);
        $this->assertArrayHasKey('netChange', $result);
        $this->assertArrayHasKey('comparisonLabel', $result);
    }

    public function testGetKpiTodayCalculatesNetRevenue(): void
    {
        $result = $this->service->getKpiToday();

        $this->assertEquals(
            $result['revenue'] - $result['returns'],
            $result['netRevenue']
        );
    }

    public function testGetKpiTodayWithBranchFilter(): void
    {
        $result = $this->service->getKpiToday(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('netRevenue', $result);
    }

    public function testGetRevenueChartReturnsLabelsAndValues(): void
    {
        $result = $this->service->getRevenueChart();

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('branchLabel', $result);
    }

    public function testGetRevenueChartWithDayPeriod(): void
    {
        $result = $this->service->getRevenueChart(['period' => 'day']);

        $this->assertIsArray($result['labels']);
        $this->assertIsArray($result['values']);
    }

    public function testGetRevenueChartWithHourPeriod(): void
    {
        $result = $this->service->getRevenueChart(['period' => 'hour']);

        $this->assertArrayHasKey('labels', $result);
    }

    public function testGetRevenueChartWithWeekdayPeriod(): void
    {
        $result = $this->service->getRevenueChart(['period' => 'weekday']);

        $this->assertArrayHasKey('labels', $result);
    }

    public function testGetRevenueChartWithInvalidPeriod(): void
    {
        $result = $this->service->getRevenueChart(['period' => 'invalid']);

        $this->assertArrayHasKey('labels', $result);
    }

    public function testGetRevenueChartWithRangeFilter(): void
    {
        $result = $this->service->getRevenueChart(['range' => 'week']);

        $this->assertArrayHasKey('total', $result);
    }

    public function testGetRevenueChartWithBranchId(): void
    {
        $result = $this->service->getRevenueChart(['branch_id' => 1]);

        $this->assertStringContainsString('Chi nhánh', $result['branchLabel']);
    }

    public function testGetTopProductsReturnsItems(): void
    {
        $result = $this->service->getTopProducts();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('metric', $result);
        $this->assertArrayHasKey('range', $result);
    }

    public function testGetTopProductsWithMetricFilter(): void
    {
        $result = $this->service->getTopProducts(['metric' => 'quantity']);

        $this->assertEquals('quantity', $result['metric']);
    }

    public function testGetTopProductsWithInvalidMetric(): void
    {
        $result = $this->service->getTopProducts(['metric' => 'invalid']);

        $this->assertEquals('net_revenue', $result['metric']);
    }

    public function testGetTopProductsWithLimitFilter(): void
    {
        $result = $this->service->getTopProducts(['limit' => 5]);

        $this->assertCount(5, $result['items']);
    }

    public function testGetTopProductsWithInvalidLimit(): void
    {
        $result = $this->service->getTopProducts(['limit' => 100]);

        // Service caps limit to 10 when invalid (>50)
        $this->assertCount(10, $result['items']);
    }

    public function testGetTopCustomersReturnsItems(): void
    {
        $result = $this->service->getTopCustomers();

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('range', $result);
    }

    public function testGetTopCustomersWithLimitFilter(): void
    {
        $result = $this->service->getTopCustomers(['limit' => 5]);

        $this->assertCount(5, $result['items']);
    }

    public function testGetActivitiesReturnsItems(): void
    {
        $result = $this->service->getActivities();

        $this->assertArrayHasKey('items', $result);
    }

    public function testGetActivitiesWithLimitFilter(): void
    {
        $result = $this->service->getActivities(['limit' => 5]);

        $this->assertCount(5, $result['items']);
    }

    public function testGetActivitiesWithInvalidLimit(): void
    {
        $result = $this->service->getActivities(['limit' => 100]);

        // Service caps limit to 15 when invalid (>50)
        $this->assertCount(15, $result['items']);
    }
}

class DashboardServiceWithRepo extends DashboardService
{
    protected \App\Repositories\Dashboard\DashboardRepository $repo;

    public function __construct(InMemoryDashboardRepo $repo)
    {
        $this->repo = $repo;
    }
}

class InMemoryDashboardRepo extends \App\Repositories\Dashboard\DashboardRepository
{
    public function __construct() {}

    public function getTodayRevenue(?int $branchId = null): float
    {
        return 1000000.0;
    }

    public function getTodayReturns(?int $branchId = null): float
    {
        return 50000.0;
    }

    public function getRevenueForPeriod(string $startDate, string $endDate, ?int $branchId = null): float
    {
        return 800000.0;
    }

    public function getRevenueByPeriod(string $period, string $range, ?int $branchId = null): array
    {
        return [
            ['label' => '2024-06-01', 'net_revenue' => 100000],
            ['label' => '2024-06-02', 'net_revenue' => 150000],
            ['label' => '2024-06-03', 'net_revenue' => 120000],
        ];
    }

    public function getTopProducts(string $metric, string $range, int $limit = 10): array
    {
        $items = [];
        for ($i = 1; $i <= $limit; $i++) {
            $items[] = ['id' => $i, 'name' => "Product $i", 'value' => 1000 * $i];
        }
        return $items;
    }

    public function getTopCustomers(string $range, int $limit = 10): array
    {
        $items = [];
        for ($i = 1; $i <= $limit; $i++) {
            $items[] = ['id' => $i, 'name' => "Customer $i", 'total' => 500000 * $i];
        }
        return $items;
    }

    public function getRecentActivities(int $limit = 15): array
    {
        $items = [];
        for ($i = 1; $i <= $limit; $i++) {
            $items[] = ['id' => $i, 'type' => 'order', 'message' => "Activity $i", 'timestamp' => date('Y-m-d H:i:s', strtotime("-$i minutes"))];
        }
        return $items;
    }
}
