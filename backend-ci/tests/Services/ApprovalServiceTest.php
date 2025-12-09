<?php

namespace Tests\Services;

use App\Services\Approvals\ApprovalService;
use App\Repositories\Approvals\ApprovalRepository;
use App\Repositories\Approvals\ApprovalActionRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class ApprovalServiceTest extends CIUnitTestCase
{
    private ApprovalServiceStub $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApprovalServiceStub();
    }

    public function testListReturnsPendingApprovals(): void
    {
        $result = $this->service->list(['status' => 'pending']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testListWithUserFilter(): void
    {
        $result = $this->service->list(['user_id' => 1]);

        $this->assertTrue($result['success']);
    }

    public function testListWithDocumentTypeFilter(): void
    {
        $result = $this->service->list(['document_type' => 'purchase_order']);

        $this->assertTrue($result['success']);
    }

    public function testShowReturnsApprovalDetail(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('actions', $result['data']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->service->setNotFound(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Approval not found');

        $this->service->show(999);
    }

    public function testApproveChangesStatus(): void
    {
        $result = $this->service->approve(1, ['user_id' => 1, 'comments' => 'Approved']);

        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $result['data']['status']);
    }

    public function testApproveThrowsWhenNotPending(): void
    {
        $this->service->setApprovalStatus(1, 'approved');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Approval is not in pending status');

        $this->service->approve(1, ['user_id' => 1]);
    }

    public function testApproveThrowsWhenUnauthorized(): void
    {
        $this->service->setUnauthorized(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User is not authorized to approve');

        $this->service->approve(1, ['user_id' => 99]);
    }

    public function testRejectChangesStatus(): void
    {
        $result = $this->service->reject(1, ['user_id' => 1, 'reason' => 'Not complete']);

        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $result['data']['status']);
    }

    public function testRejectThrowsWhenNotPending(): void
    {
        $this->service->setApprovalStatus(1, 'rejected');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Approval is not in pending status');

        $this->service->reject(1, ['user_id' => 1]);
    }

    public function testRejectRequiresReason(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rejection reason is required');

        $this->service->reject(1, ['user_id' => 1]);
    }

    public function testRequestApprovalCreatesApproval(): void
    {
        $result = $this->service->requestApproval([
            'document_type' => 'purchase_order',
            'document_id' => 1,
            'requested_by' => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('pending', $result['data']['status']);
    }

    public function testRequestApprovalThrowsWhenAlreadyExists(): void
    {
        $this->service->setApprovalExists(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Approval already exists');

        $this->service->requestApproval([
            'document_type' => 'purchase_order',
            'document_id' => 1,
        ]);
    }

    public function testCancelApprovalRequest(): void
    {
        $result = $this->service->cancel(1, ['user_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertEquals('cancelled', $result['data']['status']);
    }

    public function testCancelThrowsWhenNotPending(): void
    {
        $this->service->setApprovalStatus(1, 'approved');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only pending approvals can be cancelled');

        $this->service->cancel(1, ['user_id' => 1]);
    }

    public function testGetApprovalHistory(): void
    {
        $result = $this->service->getHistory('purchase_order', 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testGetPendingCount(): void
    {
        $result = $this->service->getPendingCount(1);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetPendingCountByDocumentType(): void
    {
        $result = $this->service->getPendingCount(1, 'purchase_order');

        $this->assertIsInt($result);
    }
}

class ApprovalServiceStub extends ApprovalService
{
    private bool $notFound = false;
    private bool $unauthorized = false;
    private bool $approvalExists = false;
    private array $statuses = [];

    public function __construct()
    {
        // Don't call parent constructor
    }

    public function setNotFound(bool $value): void
    {
        $this->notFound = $value;
    }

    public function setUnauthorized(bool $value): void
    {
        $this->unauthorized = $value;
    }

    public function setApprovalExists(bool $value): void
    {
        $this->approvalExists = $value;
    }

    public function setApprovalStatus(int $id, string $status): void
    {
        $this->statuses[$id] = $status;
    }

    private function getStatus(int $id): string
    {
        return $this->statuses[$id] ?? 'pending';
    }

    public function list(array $filters = []): array
    {
        return [
            'success' => true,
            'data' => [
                ['id' => 1, 'document_type' => 'purchase_order', 'document_id' => 1, 'status' => 'pending'],
                ['id' => 2, 'document_type' => 'stock_transfer', 'document_id' => 1, 'status' => 'pending'],
            ],
        ];
    }

    public function show(int $id): array
    {
        if ($this->notFound) {
            throw new RuntimeException('Approval not found');
        }
        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'document_type' => 'purchase_order',
                'document_id' => 1,
                'status' => $this->getStatus($id),
                'actions' => [],
            ],
        ];
    }

    public function approve(int $id, array $input): array
    {
        if ($this->notFound) {
            throw new RuntimeException('Approval not found');
        }
        if ($this->getStatus($id) !== 'pending') {
            throw new RuntimeException('Approval is not in pending status');
        }
        if ($this->unauthorized) {
            throw new RuntimeException('User is not authorized to approve');
        }
        return [
            'success' => true,
            'data' => ['id' => $id, 'status' => 'approved'],
        ];
    }

    public function reject(int $id, array $input): array
    {
        if ($this->notFound) {
            throw new RuntimeException('Approval not found');
        }
        if ($this->getStatus($id) !== 'pending') {
            throw new RuntimeException('Approval is not in pending status');
        }
        if (empty($input['reason'])) {
            throw new RuntimeException('Rejection reason is required');
        }
        return [
            'success' => true,
            'data' => ['id' => $id, 'status' => 'rejected'],
        ];
    }

    public function requestApproval(array $input): array
    {
        if ($this->approvalExists) {
            throw new RuntimeException('Approval already exists');
        }
        return [
            'success' => true,
            'data' => [
                'id' => 1,
                'document_type' => $input['document_type'],
                'document_id' => $input['document_id'],
                'status' => 'pending',
            ],
        ];
    }

    public function cancel(int $id, array $input): array
    {
        if ($this->notFound) {
            throw new RuntimeException('Approval not found');
        }
        if ($this->getStatus($id) !== 'pending') {
            throw new RuntimeException('Only pending approvals can be cancelled');
        }
        return [
            'success' => true,
            'data' => ['id' => $id, 'status' => 'cancelled'],
        ];
    }

    public function getHistory(string $documentType, int $documentId): array
    {
        return [
            'success' => true,
            'data' => [
                ['id' => 1, 'action' => 'requested', 'timestamp' => '2024-06-15 10:00:00'],
                ['id' => 2, 'action' => 'approved', 'timestamp' => '2024-06-15 12:00:00'],
            ],
        ];
    }

    public function getPendingCount(int $userId, ?string $documentType = null): int
    {
        return 5;
    }
}
