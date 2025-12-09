<?php

namespace Tests\Services;

use App\Services\Inventory\StockTransferService;
use App\Repositories\Inventory\StockTransferRepository;
use App\Validators\StockTransferValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @agent-test: StockTransferService
 * @agent-pattern: Service test with injected repository
 */
class StockTransferServiceTest extends CIUnitTestCase
{
    private StockTransferService $service;
    private InMemoryStockTransferRepo $repo;
    private StockTransferValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryStockTransferRepo();
        $this->validator = new StockTransferValidator();
        $this->service = new StockTransferService($this->repo, $this->validator, null);
    }

    public function testListReturnsDataWithPagination(): void
    {
        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('summary', $result);
    }

    public function testListWithPageAndLimit(): void
    {
        $result = $this->service->list(['page' => 2, 'limit' => 5]);

        $this->assertSame(2, $result['pagination']['page']);
        $this->assertSame(5, $result['pagination']['limit']);
    }

    public function testListWithSearch(): void
    {
        $result = $this->service->list(['search' => 'ST-001']);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testListWithStatusFilter(): void
    {
        $result = $this->service->list(['status' => 'completed']);

        $this->assertTrue($result['success']);
    }

    public function testShowReturnsTransferDetails(): void
    {
        $result = $this->service->show('ST-001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('transfer', $result);
        $this->assertSame('ST-001', $result['transfer']['code']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->show('NONEXISTENT');
    }

    public function testCreateTransferReturnsNewTransfer(): void
    {
        $data = [
            'from_branch_id' => 1,
            'to_branch_id' => 2,
            'items' => [
                [
                    'product_id' => 1,
                    'product_code' => 'SKU-001',
                    'product_name' => 'Product 1',
                    'quantity_sent' => 10,
                    'unit_price' => 50000,
                ],
            ],
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']['code']);
    }

    public function testCreateTransferWithMultipleItems(): void
    {
        $data = [
            'from_branch_id' => 1,
            'to_branch_id' => 2,
            'items' => [
                ['product_id' => 1, 'quantity_sent' => 5, 'unit_price' => 10000],
                ['product_id' => 2, 'quantity_sent' => 3, 'unit_price' => 20000],
            ],
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
    }

    public function testCreateTransferValidatesInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(['from_branch_id' => 1]); // Missing to_branch_id and items
    }

    public function testListReturnsCorrectPaginationTotalPages(): void
    {
        $result = $this->service->list(['limit' => 2]);

        $expectedPages = (int) ceil($result['pagination']['total'] / 2);
        $this->assertSame($expectedPages, $result['pagination']['total_pages']);
    }
}

class InMemoryStockTransferRepo extends StockTransferRepository
{
    private array $transfers = [];
    private int $nextId = 1;

    public function __construct()
    {
        $this->transfers = [
            ['id' => 1, 'code' => 'ST-001', 'status' => 'completed', 'from_branch_id' => 1, 'to_branch_id' => 2, 'from_branch_name' => 'Branch 1', 'to_branch_name' => 'Branch 2', 'total_items' => 2, 'quantity_sent' => 10, 'value_sent' => 500000, 'quantity_received' => 10, 'value_received' => 500000, 'transfer_date' => '2024-01-15', 'receive_date' => '2024-01-16', 'created_at' => '2024-01-15 10:00:00', 'creator_name' => 'Admin', 'receiver_name' => 'Staff', 'notes' => '', 'receiving_notes' => ''],
            ['id' => 2, 'code' => 'ST-002', 'status' => 'draft', 'from_branch_id' => 1, 'to_branch_id' => 3, 'from_branch_name' => 'Branch 1', 'to_branch_name' => 'Branch 3', 'total_items' => 1, 'quantity_sent' => 5, 'value_sent' => 100000, 'quantity_received' => 0, 'value_received' => 0, 'transfer_date' => '2024-01-16', 'receive_date' => null, 'created_at' => '2024-01-16 10:00:00', 'creator_name' => 'Admin', 'receiver_name' => '', 'notes' => '', 'receiving_notes' => ''],
            ['id' => 3, 'code' => 'ST-003', 'status' => 'in_transit', 'from_branch_id' => 2, 'to_branch_id' => 1, 'from_branch_name' => 'Branch 2', 'to_branch_name' => 'Branch 1', 'total_items' => 3, 'quantity_sent' => 15, 'value_sent' => 750000, 'quantity_received' => 0, 'value_received' => 0, 'transfer_date' => '2024-01-17', 'receive_date' => null, 'created_at' => '2024-01-17 10:00:00', 'creator_name' => 'Admin', 'receiver_name' => '', 'notes' => '', 'receiving_notes' => ''],
        ];
        $this->nextId = 4;
    }

    public function findAll(array $filters = []): array
    {
        $result = $this->transfers;

        if (isset($filters['status'])) {
            $result = array_filter($result, fn($t) => $t['status'] === $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = strtolower($filters['search']);
            $result = array_filter($result, fn($t) => str_contains(strtolower($t['code']), $search));
        }

        $limit = $filters['limit'] ?? 15;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        return array_slice(array_values($result), $offset, $limit);
    }

    public function count(array $filters = []): int
    {
        return count($this->transfers);
    }

    public function getSummary(array $filters = []): array
    {
        return [
            'total_transfers' => count($this->transfers),
            'total_quantity' => array_sum(array_column($this->transfers, 'quantity_sent')),
            'total_value' => array_sum(array_column($this->transfers, 'value_sent')),
        ];
    }

    public function findByCode(string $code): ?array
    {
        foreach ($this->transfers as $transfer) {
            if ($transfer['code'] === $code) {
                return $transfer;
            }
        }
        return null;
    }

    public function findItems(int $transferId): array
    {
        return [
            ['id' => 1, 'transfer_id' => $transferId, 'product_id' => 1, 'product_code' => 'SKU-001', 'product_name' => 'Product 1', 'quantity_sent' => 5, 'quantity_received' => 5, 'unit_price' => 50000, 'total_price' => 250000],
            ['id' => 2, 'transfer_id' => $transferId, 'product_id' => 2, 'product_code' => 'SKU-002', 'product_name' => 'Product 2', 'quantity_sent' => 5, 'quantity_received' => 5, 'unit_price' => 50000, 'total_price' => 250000],
        ];
    }

    public function nextCode(): string
    {
        return 'ST-' . str_pad((string) $this->nextId++, 3, '0', STR_PAD_LEFT);
    }

    public function insert(array $data): int
    {
        $id = $this->nextId++;
        $data['id'] = $id;
        $this->transfers[] = $data;
        return $id;
    }

    public function insertItems(int $transferId, array $items): void
    {
        // In-memory, no-op
    }

    public function create(array $data, array $items = []): array
    {
        $id = $this->nextId++;
        $data['id'] = $id;
        $data['code'] = $data['code'] ?? $this->nextCode();
        $data['from_branch_name'] = 'Branch ' . $data['from_branch_id'];
        $data['to_branch_name'] = 'Branch ' . $data['to_branch_id'];
        $data['quantity_received'] = 0;
        $data['value_received'] = 0;
        $data['receive_date'] = null;
        $data['creator_name'] = 'Admin';
        $data['receiver_name'] = '';
        $data['receiving_notes'] = '';
        $this->transfers[] = $data;
        return $data;
    }
}
