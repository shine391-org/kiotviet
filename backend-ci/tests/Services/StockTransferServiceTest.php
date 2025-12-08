<?php

namespace Tests\Services;

use App\Services\Inventory\StockTransferService;
use App\Repositories\Inventory\StockTransferRepository;
use App\Validators\StockTransferValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @agent-test: StockTransferService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class StockTransferServiceTest extends CIUnitTestCase
{
    private StockTransferService $service;
    private StockTransferServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockTransferServiceFakeRepo();
        $validator = new StockTransferValidator();
        $this->service = new StockTransferService($this->repo, $validator);
    }

    public function testListReturnsTransfersWithPagination(): void
    {
        $result = $this->service->list(['page' => 1, 'limit' => 15]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testShowReturnsTransferDetail(): void
    {
        $result = $this->service->show('TRF-001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transfer', $result);
        $this->assertSame('TRF-001', $result['transfer']['code']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->show('INVALID');
    }

    public function testCreateReturnsNewTransfer(): void
    {
        $data = [
            'from_branch_id' => 1,
            'to_branch_id' => 2,
            'items' => [
                [
                    'product_id' => 1,
                    'product_code' => 'PRD001',
                    'product_name' => 'Product 1',
                    'quantity_sent' => 10,
                    'unit_price' => 50000,
                ],
            ],
            'notes' => 'Test transfer',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('draft', $result['data']['status']);
    }

    public function testUpdateModifiesTransfer(): void
    {
        $result = $this->service->update(1, [
            'notes' => 'Updated notes',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->update(999, ['notes' => 'Test']);
    }

    public function testUpdateThrowsWhenNotDraft(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chỉ có thể cập nhật phiếu ở trạng thái nháp');

        $this->service->update(2, ['notes' => 'Test']); // ID 2 is in_transit
    }

    public function testSubmitChangesStatusToInTransit(): void
    {
        $result = $this->service->submit(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSubmitThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->submit(999);
    }

    public function testSubmitThrowsWhenNotDraft(): void
    {
        $this->repo->setTransferStatus(2, 'received');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Chỉ có thể gửi phiếu ở trạng thái nháp');

        $this->service->submit(2);
    }

    public function testSubmitByCodeWorks(): void
    {
        $result = $this->service->submitByCode('TRF-001');

        $this->assertTrue($result['success']);
    }

    public function testReceiveMarksAsReceived(): void
    {
        $result = $this->service->receive(2, [
            'received_by' => 1,
            'receiving_notes' => 'All items received',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testReceiveThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->receive(999, []);
    }

    public function testReceiveThrowsWhenCancelled(): void
    {
        $this->repo->setTransferStatus(1, 'cancelled');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không thể nhận hàng cho phiếu này');

        $this->service->receive(1, []);
    }

    public function testCancelMarksAsCancelled(): void
    {
        $result = $this->service->cancel(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCancelThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->cancel(999);
    }

    public function testCancelThrowsWhenAlreadyReceived(): void
    {
        $this->repo->setTransferStatus(1, 'received');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không thể hủy phiếu đã nhận hàng');

        $this->service->cancel(1);
    }

    public function testDuplicateCreatesNewTransfer(): void
    {
        $result = $this->service->duplicate('TRF-001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('draft', $result['data']['status']);
    }

    public function testDuplicateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->duplicate('INVALID');
    }

    public function testSaveNotesUpdatesNotes(): void
    {
        $result = $this->service->saveNotes('TRF-001', 'New notes');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSaveNotesThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy phiếu chuyển hàng');

        $this->service->saveNotes('INVALID', 'Test');
    }
}

class StockTransferServiceFakeRepo extends StockTransferRepository
{
    private array $transfers = [];

    public function __construct()
    {
        $this->transfers = [
            1 => [
                'id' => 1,
                'code' => 'TRF-001',
                'status' => 'draft',
                'from_branch_id' => 1,
                'to_branch_id' => 2,
                'from_branch_name' => 'Branch 1',
                'to_branch_name' => 'Branch 2',
                'transfer_date' => '2024-06-15 10:00:00',
                'receive_date' => null,
                'total_items' => 2,
                'quantity_sent' => 20,
                'value_sent' => 1000000,
                'quantity_received' => 0,
                'value_received' => 0,
                'notes' => 'Test transfer',
                'receiving_notes' => null,
                'creator_name' => 'Admin',
                'created_by' => 1,
                'created_at' => '2024-06-15 10:00:00',
            ],
            2 => [
                'id' => 2,
                'code' => 'TRF-002',
                'status' => 'in_transit',
                'from_branch_id' => 1,
                'to_branch_id' => 3,
                'from_branch_name' => 'Branch 1',
                'to_branch_name' => 'Branch 3',
                'transfer_date' => '2024-06-16 10:00:00',
                'receive_date' => null,
                'total_items' => 1,
                'quantity_sent' => 5,
                'value_sent' => 250000,
                'quantity_received' => 0,
                'value_received' => 0,
                'notes' => null,
                'receiving_notes' => null,
                'creator_name' => 'Staff',
                'created_by' => 2,
                'created_at' => '2024-06-16 10:00:00',
            ],
        ];
    }

    public function setTransferStatus(int $id, string $status): void
    {
        if (isset($this->transfers[$id])) {
            $this->transfers[$id]['status'] = $status;
        }
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->transfers);
    }

    public function count(array $filters = []): int
    {
        return count($this->transfers);
    }

    public function getSummary(array $filters = []): array
    {
        return [
            'total_transfers' => count($this->transfers),
            'total_value' => 1250000,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->transfers[$id] ?? null;
    }

    public function findByCode(string $code): ?array
    {
        foreach ($this->transfers as $t) {
            if ($t['code'] === $code) {
                return $t;
            }
        }
        return null;
    }

    public function findItems(int $transferId): array
    {
        return [
            [
                'id' => 1,
                'product_id' => 1,
                'variant_id' => null,
                'product_code' => 'PRD001',
                'product_name' => 'Product 1',
                'unit' => 'pcs',
                'quantity_sent' => 10,
                'quantity_received' => 0,
                'unit_price' => 50000,
                'total_price' => 500000,
            ],
        ];
    }

    public function nextCode(): string
    {
        return 'TRF-' . sprintf('%03d', count($this->transfers) + 1);
    }

    public function create(array $data, array $items = []): array
    {
        $id = max(array_keys($this->transfers)) + 1;
        $data['id'] = $id;
        $this->transfers[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data): array
    {
        if (! isset($this->transfers[$id])) {
            return [];
        }
        $this->transfers[$id] = array_merge($this->transfers[$id], $data);
        return $this->transfers[$id];
    }

    public function updateItems(int $id, array $items): void
    {
        // Fake implementation
    }
}
