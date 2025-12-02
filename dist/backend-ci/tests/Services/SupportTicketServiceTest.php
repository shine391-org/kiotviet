<?php

namespace Tests\Services;

use App\Services\Support\SupportTicketService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: SupportTicketService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class SupportTicketServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private SupportTicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new SupportTicketService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_ticket_with_default_status()
    {
        $result = $this->service->create(['subject' => 'Printer broken', 'priority' => 'high']);

        $this->assertTrue($result['success']);
        $ticket = $result['data'];
        $this->assertEquals('open', $ticket['status']);
        $this->assertEquals('high', $ticket['priority']);
        $this->assertGreaterThan(0, $ticket['id']);
    }

    /** @test */
    public function it_changes_status_and_logs_event()
    {
        $ticket = $this->service->create(['subject' => 'Login issue'])['data'];

        $updated = $this->service->changeStatus($ticket['id'], 'working');
        $this->assertEquals('working', $updated['data']['status']);

        $count = $this->db->table('ticket_events')->where('ticket_id', $ticket['id'])->countAllResults();
        $this->assertEquals(2, $count); // created + status_change
    }

    /** @test */
    public function it_assigns_ticket()
    {
        $ticket = $this->service->create(['subject' => 'Need help'])['data'];

        $res = $this->service->assign($ticket['id'], 5);
        $this->assertEquals(5, $res['data']['assigned_to']);

        $event = $this->db->table('ticket_events')->where('ticket_id', $ticket['id'])->where('event_type', 'assigned')->get()->getRowArray();
        $this->assertNotNull($event);
    }

    /** @test */
    public function it_validates_status_transition_input()
    {
        $ticket = $this->service->create(['subject' => 'Invalid status'])['data'];

        $this->expectException(\InvalidArgumentException::class);
        $this->service->changeStatus($ticket['id'], 'unknown');
    }
}
