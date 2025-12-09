<?php

namespace Tests\Services;

use App\Services\Support\SupportTicketService;
use App\Repositories\Support\SupportTicketRepository;
use App\Repositories\Support\TicketEventRepository;
use App\Validators\SupportTicketValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class SupportTicketServiceTest extends CIUnitTestCase
{
    private SupportTicketService $service;
    private SupportTicketFakeRepo $ticketRepo;
    private TicketEventFakeRepo $eventRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketRepo = new SupportTicketFakeRepo();
        $this->eventRepo = new TicketEventFakeRepo();
        $validator = new class extends SupportTicketValidator {
            public function validateCreate(array $data): array { return $data; }
            public function validateUpdate(array $data): array { return $data; }
            public function validateStatus(string $status): string { return $status; }
            public function validateAssignment($assigneeId): int { return (int) $assigneeId; }
        };
        $this->service = new SupportTicketService($this->ticketRepo, $this->eventRepo, $validator);
    }

    public function testCreateSuccess(): void
    {
        $data = ['subject' => 'Help needed', 'description' => 'Issue'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateLogsEvent(): void
    {
        $data = ['subject' => 'Test'];
        $this->service->create($data);
        
        $this->assertGreaterThan(0, $this->eventRepo->count());
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['subject' => 'Updated']);
        
        $this->assertTrue($result['success']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ticket not found');
        
        $this->service->update(999, ['subject' => 'Test']);
    }

    public function testUpdateReturnsOriginalWhenNoData(): void
    {
        $result = $this->service->update(1, []);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testChangeStatusSuccess(): void
    {
        $result = $this->service->changeStatus(1, 'in_progress');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('in_progress', $result['data']['status']);
    }

    public function testChangeStatusThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->changeStatus(999, 'closed');
    }

    public function testAssignSuccess(): void
    {
        $result = $this->service->assign(1, 5);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['data']['assignee_id']);
    }

    public function testAssignThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->assign(999, 5);
    }

    public function testGetReturnsTicket(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ticket not found');
        
        $this->service->get(999);
    }
}

class SupportTicketFakeRepo extends SupportTicketRepository
{
    private array $ticketData = [
        1 => ['id' => 1, 'subject' => 'Test', 'status' => 'open', 'assignee_id' => null],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->ticketData[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->ticketData) ?: [0]) + 1;
        $this->ticketData[$id] = array_merge($data, ['id' => $id, 'status' => 'open']);
        return $this->ticketData[$id];
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->ticketData[$id])) {
            $this->ticketData[$id] = array_merge($this->ticketData[$id], $data);
        }
        return $this->ticketData[$id] ?? [];
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (isset($this->ticketData[$id])) {
            $this->ticketData[$id]['status'] = $status;
            return true;
        }
        return false;
    }

    public function assign(int $id, ?int $assignedTo): bool
    {
        if (isset($this->ticketData[$id])) {
            $this->ticketData[$id]['assignee_id'] = $assignedTo;
            return true;
        }
        return false;
    }
}

class TicketEventFakeRepo extends TicketEventRepository
{
    private array $eventData = [];

    public function __construct() {}

    public function create(array $data): array
    {
        $id = count($this->eventData) + 1;
        $this->eventData[$id] = array_merge($data, ['id' => $id]);
        return $this->eventData[$id];
    }

    public function count(): int
    {
        return count($this->eventData);
    }
}
