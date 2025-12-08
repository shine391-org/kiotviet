<?php

namespace Tests\Services;

use App\Services\Inventory\StockDisposalService;
use App\Repositories\Inventory\StockDisposalRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @agent-test: StockDisposalService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class StockDisposalServiceTest extends CIUnitTestCase
{
    private StockDisposalService $service;
    private StockDisposalServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockDisposalServiceFakeRepo();
        $this->service = new StockDisposalService($this->repo);
    }

    public function testListReturnsDisposalsWithPagination(): void
    {
        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame(1, $result['pagination']['page']);
        $this->assertSame(10, $result['pagination']['limit']);
        $this->assertSame(2, $result['pagination']['total']);
    }

    public function testListTransformsRowsCorrectly(): void
    {
        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $item = $result['data'][0];
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('dispose_code', $item);
        $this->assertArrayHasKey('branch_id', $item);
        $this->assertArrayHasKey('status', $item);
        $this->assertArrayHasKey('disposed_at', $item);
        $this->assertArrayHasKey('total_quantity', $item);
        $this->assertArrayHasKey('total_value', $item);
    }

    public function testShowReturnsDisposalById(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('XH24-0001', $result['data']['dispose_code']);
        $this->assertArrayHasKey('items', $result['data']);
    }

    public function testShowReturnsDisposalByCode(): void
    {
        $result = $this->service->show('XH24-0001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu xuất hủy');

        $this->service->show(999);
    }

    public function testCreateReturnsNewDisposal(): void
    {
        $input = [
            'branch_id' => 1,
            'notes' => 'Expired products',
            'items' => [
                [
                    'product_id' => 1,
                    'sku' => 'SKU001',
                    'name' => 'Product 1',
                    'quantity' => 5,
                    'cost_price' => 100000,
                ],
            ],
        ];

        $result = $this->service->create($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('draft', $result['data']['status']);
        $this->assertSame('Expired products', $result['data']['notes']);
    }

    public function testCreateSetsDefaultBranchId(): void
    {
        $input = [
            'notes' => 'Test disposal',
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];

        $result = $this->service->create($input);

        $this->assertTrue($result['success']);
        // Branch ID defaults to 1 if not set
        $this->assertArrayHasKey('branch_id', $result['data']);
    }

    public function testUpdateReturnsUpdatedDisposal(): void
    {
        $result = $this->service->update(1, ['notes' => 'Updated notes']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotDraft(): void
    {
        $this->repo->fakeDisposals[1]['status'] = 'completed';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chỉ có thể sửa phiếu tạm');

        $this->service->update(1, ['notes' => 'Test']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu xuất hủy');

        $this->service->update(999, ['notes' => 'Test']);
    }

    public function testUpdateUpdatesItems(): void
    {
        $result = $this->service->update(1, [
            'notes' => 'Updated',
            'items' => [
                ['product_id' => 2, 'quantity' => 10, 'cost_price' => 50000],
            ],
        ]);

        $this->assertTrue($result['success']);
    }

    public function testCompleteTransitionsToCompleted(): void
    {
        $result = $this->service->complete(1, ['executor_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['data']['status']);
    }

    public function testCompleteThrowsWhenNotDraft(): void
    {
        $this->repo->fakeDisposals[1]['status'] = 'completed';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Phiếu đã được xử lý');

        $this->service->complete(1, []);
    }

    public function testCompleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu xuất hủy');

        $this->service->complete(999, []);
    }

    public function testCancelTransitionsToCancelled(): void
    {
        $result = $this->service->cancel(1, ['notes' => 'Cancelled by user']);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['data']['status']);
    }

    public function testCancelThrowsWhenCompleted(): void
    {
        $this->repo->fakeDisposals[1]['status'] = 'completed';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không thể hủy phiếu đã hoàn thành');

        $this->service->cancel(1, []);
    }

    public function testCancelThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu xuất hủy');

        $this->service->cancel(999, []);
    }

    public function testListWithFilters(): void
    {
        $result = $this->service->list([
            'branch_id' => 1,
            'status' => 'draft',
        ]);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testTotalsInListResponse(): void
    {
        $result = $this->service->list([]);

        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('total_quantity', $result['totals']);
        $this->assertArrayHasKey('total_value', $result['totals']);
        $this->assertIsFloat($result['totals']['total_quantity']);
        $this->assertIsFloat($result['totals']['total_value']);
    }
}

// Fake repository class for testing

class StockDisposalServiceFakeRepo extends StockDisposalRepository
{
    public array $fakeDisposals = [];
    public array $fakeItems = [];
    private int $nextId = 3;

    public function __construct()
    {
        $this->fakeDisposals = [
            1 => [
                'id' => 1,
                'code' => 'XH24-0001',
                'branch_id' => 1,
                'branch_name' => 'Main Branch',
                'status' => 'draft',
                'notes' => 'Damaged goods',
                'disposed_at' => '2024-01-01 10:00:00',
                'created_at' => '2024-01-01 09:00:00',
                'created_by' => 1,
                'creator_name' => 'Admin',
                'executor_id' => null,
                'executor_name' => null,
                'total_quantity' => 10,
                'total_value' => 1000000,
            ],
            2 => [
                'id' => 2,
                'code' => 'XH24-0002',
                'branch_id' => 1,
                'branch_name' => 'Main Branch',
                'status' => 'completed',
                'notes' => 'Expired items',
                'disposed_at' => '2024-01-02 10:00:00',
                'created_at' => '2024-01-02 09:00:00',
                'created_by' => 1,
                'creator_name' => 'Admin',
                'executor_id' => 1,
                'executor_name' => 'Admin',
                'total_quantity' => 5,
                'total_value' => 500000,
            ],
        ];

        $this->fakeItems = [
            1 => [
                [
                    'id' => 1,
                    'disposal_id' => 1,
                    'product_id' => 1,
                    'variant_id' => null,
                    'sku' => 'SKU001',
                    'name' => 'Product 1',
                    'quantity' => 10,
                    'cost_price' => 100000,
                    'disposal_value' => 1000000,
                ],
            ],
            2 => [
                [
                    'id' => 2,
                    'disposal_id' => 2,
                    'product_id' => 2,
                    'variant_id' => null,
                    'sku' => 'SKU002',
                    'name' => 'Product 2',
                    'quantity' => 5,
                    'cost_price' => 100000,
                    'disposal_value' => 500000,
                ],
            ],
        ];
    }

    public function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect('tests');
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->fakeDisposals);
    }

    public function count(array $filters = []): int
    {
        return count($this->fakeDisposals);
    }

    public function findById(int $id): ?array
    {
        return $this->fakeDisposals[$id] ?? null;
    }

    public function findByCode(string $code): ?array
    {
        foreach ($this->fakeDisposals as $disposal) {
            if ($disposal['code'] === $code) {
                return $disposal;
            }
        }
        return null;
    }

    public function findItems(int $disposalId): array
    {
        return $this->fakeItems[$disposalId] ?? [];
    }

    public function create(array $data, array $items = []): array
    {
        $id = $this->nextId++;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');

        $totalQuantity = 0;
        $totalValue = 0;
        $disposalItems = [];
        foreach ($items as $idx => $item) {
            $disposalValue = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
            $disposalItems[] = $item + [
                'id' => $id * 100 + $idx,
                'disposal_id' => $id,
                'disposal_value' => $disposalValue,
            ];
            $totalQuantity += (float) ($item['quantity'] ?? 0);
            $totalValue += $disposalValue;
        }

        $data['total_quantity'] = $totalQuantity;
        $data['total_value'] = $totalValue;
        $data['branch_name'] = 'Main Branch';
        $data['creator_name'] = null;
        $data['executor_name'] = null;

        $this->fakeDisposals[$id] = $data;
        $this->fakeItems[$id] = $disposalItems;

        return $data;
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->fakeDisposals[$id])) {
            $this->fakeDisposals[$id] = array_merge($this->fakeDisposals[$id], $data);
        }
        return $this->fakeDisposals[$id] ?? [];
    }

    public function updateItems(int $disposalId, array $items): void
    {
        $totalQuantity = 0;
        $totalValue = 0;
        $disposalItems = [];
        
        foreach ($items as $idx => $item) {
            $disposalValue = ($item['quantity'] ?? 0) * ($item['cost_price'] ?? 0);
            $disposalItems[] = $item + [
                'id' => $disposalId * 100 + $idx,
                'disposal_id' => $disposalId,
                'disposal_value' => $disposalValue,
            ];
            $totalQuantity += (float) ($item['quantity'] ?? 0);
            $totalValue += $disposalValue;
        }

        $this->fakeItems[$disposalId] = $disposalItems;
        
        if (isset($this->fakeDisposals[$disposalId])) {
            $this->fakeDisposals[$disposalId]['total_quantity'] = $totalQuantity;
            $this->fakeDisposals[$disposalId]['total_value'] = $totalValue;
        }
    }

    public function delete(int $id): bool
    {
        if (!isset($this->fakeDisposals[$id])) {
            return false;
        }
        unset($this->fakeDisposals[$id]);
        unset($this->fakeItems[$id]);
        return true;
    }

    public function nextCode(): string
    {
        $year = date('y');
        $count = count($this->fakeDisposals);
        return "XH{$year}-" . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getSummary(array $filters = []): array
    {
        $totalQuantity = 0;
        $totalValue = 0;
        foreach ($this->fakeDisposals as $disposal) {
            $totalQuantity += (float) ($disposal['total_quantity'] ?? 0);
            $totalValue += (float) ($disposal['total_value'] ?? 0);
        }
        return [
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'count' => count($this->fakeDisposals),
        ];
    }
}
