<?php

namespace Tests\Services;

use App\Services\Returns\ReturnService;
use App\Repositories\Returns\ReturnRepository;
use App\Transformers\ReturnTransformer;
use App\Validators\ReturnValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: ReturnService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class ReturnServiceTest extends CIUnitTestCase
{
    private ReturnService $service;
    private ReturnServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ReturnServiceFakeRepo();
        $validator = new ReturnValidator();
        $transformer = new ReturnTransformer();
        $this->service = new ReturnService($this->repo, $validator, $transformer);
    }

    public function testListReturnsWithPagination(): void
    {
        $result = $this->service->list(['page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testGetReturnsDetail(): void
    {
        $result = $this->service->get(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('RTN-001', $result['data']['return_number']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Return not found');
        $this->service->get(999);
    }

    public function testCreateReturnSuccess(): void
    {
        $data = [
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [
                ['order_item_id' => 100, 'quantity_returned' => 1, 'item_condition' => 'new'],
            ],
            'reason' => 'defective',
            'refund_method' => 'cash',
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('pending', $result['data']['status']);
    }

    public function testCreateValidatesOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testCreateValidatesCustomerId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'order_id' => 10,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testCreateValidatesItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Validator requires items field first
        $this->expectExceptionMessage('items field is required');

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [],
            'reason' => 'defective',
        ]);
    }

    public function testCreateValidatesReason(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'invalid_reason',
        ]);
    }

    public function testCreateRequiresReasonDetailForOther(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason detail is required');

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'other',
        ]);
    }

    public function testCreateValidatesItemQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity_returned must be > 0');

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 0]],
            'reason' => 'defective',
        ]);
    }

    public function testCreateValidatesItemCondition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('condition is invalid');

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1, 'item_condition' => 'invalid']],
            'reason' => 'defective',
        ]);
    }

    public function testCreateThrowsWhenOrderNotFound(): void
    {
        $this->repo->setOrderExists(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order not found');

        $this->service->create([
            'order_id' => 999,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testCreateThrowsWhenOrderNotCompleted(): void
    {
        $this->repo->setOrderStatus('pending');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Can only return completed orders');

        $this->service->create([
            'order_id' => 10,
            'customer_id' => 1,
            'items' => [['order_item_id' => 100, 'quantity_returned' => 1]],
            'reason' => 'defective',
        ]);
    }

    public function testApproveSuccess(): void
    {
        $result = $this->service->approve(1, [
            'user_id' => 1,
            'refund_method' => 'bank_transfer',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testApproveThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Return not found');

        $this->service->approve(999, [
            'user_id' => 1,
            'refund_method' => 'cash',
        ]);
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only pending returns can be approved');

        $this->service->approve(2, [ // ID 2 is already approved
            'user_id' => 1,
            'refund_method' => 'cash',
        ]);
    }

    public function testRejectSuccess(): void
    {
        $result = $this->service->reject(1, ['user_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRejectThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Return not found');

        $this->service->reject(999, ['user_id' => 1]);
    }

    public function testRejectThrowsWhenNotPending(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only pending returns can be rejected');

        $this->service->reject(2, ['user_id' => 1]);
    }

    public function testCompleteSuccess(): void
    {
        $result = $this->service->complete(2, ['user_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCompleteThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Return not found');

        $this->service->complete(999, ['user_id' => 1]);
    }

    public function testCompleteThrowsWhenNotApproved(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only approved returns can be completed');

        $this->service->complete(1, ['user_id' => 1]); // ID 1 is pending
    }
}

class ReturnServiceFakeRepo extends ReturnRepository
{
    private array $fakeReturns = [];
    private bool $orderExists = true;
    private string $orderStatus = 'completed';

    public function __construct()
    {
        $this->fakeReturns = [
            1 => [
                'id' => 1,
                'return_number' => 'RTN-001',
                'order_id' => 10,
                'customer_id' => 1,
                'return_amount' => 100000,
                'refund_amount' => 100000,
                'status' => 'pending',
                'reason' => 'defective',
                'items' => [],
                'version' => 1,
                'created_at' => '2024-06-01 00:00:00',
            ],
            2 => [
                'id' => 2,
                'return_number' => 'RTN-002',
                'order_id' => 11,
                'customer_id' => 2,
                'return_amount' => 200000,
                'refund_amount' => 200000,
                'status' => 'approved',
                'reason' => 'wrong_item',
                'items' => [],
                'version' => 1,
                'created_at' => '2024-06-02 00:00:00',
            ],
        ];
    }

    public function setOrderExists(bool $value): void
    {
        $this->orderExists = $value;
    }

    public function setOrderStatus(string $status): void
    {
        $this->orderStatus = $status;
    }

    public function findAll(array $filters = []): array
    {
        return array_values($this->fakeReturns);
    }

    public function count(array $filters = []): int
    {
        return count($this->fakeReturns);
    }

    public function findById(int $id): ?array
    {
        return $this->fakeReturns[$id] ?? null;
    }

    public function orderWithItems(int $orderId): ?array
    {
        if (! $this->orderExists) {
            return null;
        }
        return [
            'id' => $orderId,
            'customer_id' => 1,
            'status' => $this->orderStatus,
            'shipping_fee' => 30000,
            'completed_at' => date('Y-m-d H:i:s'),
            'branch_id' => 1,
            'items' => [
                ['id' => 100, 'product_id' => 1, 'quantity' => 5, 'final_price' => 20000],
                ['id' => 101, 'product_id' => 2, 'quantity' => 3, 'final_price' => 30000],
            ],
        ];
    }

    public function alreadyReturnedQty(int $orderItemId): float
    {
        return 0;
    }

    public function nextNumber(int $orderId): string
    {
        return 'RTN-' . sprintf('%03d', count($this->fakeReturns) + 1);
    }

    public function create(array $data, array $items): array
    {
        $id = max(array_keys($this->fakeReturns)) + 1;
        $data['id'] = $id;
        $data['items'] = $items;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['version'] = 1;
        $this->fakeReturns[$id] = $data;
        return $data;
    }

    public function transition(int $id, string $toStatus, array $extra = [], ?int $expectedVersion = null): array
    {
        if (! isset($this->fakeReturns[$id])) {
            return [];
        }
        $this->fakeReturns[$id]['status'] = $toStatus;
        $this->fakeReturns[$id] = array_merge($this->fakeReturns[$id], $extra);
        $this->fakeReturns[$id]['version'] = ($this->fakeReturns[$id]['version'] ?? 0) + 1;
        return $this->fakeReturns[$id];
    }
}
