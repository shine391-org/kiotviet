<?php

namespace Tests\Services;

use App\Services\Support\CommunicationService;
use App\Services\Support\SupportTicketService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CommunicationService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class CommunicationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CommunicationService $service;
    private SupportTicketService $tickets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new CommunicationService();
        $this->tickets = new SupportTicketService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_communication_for_ticket()
    {
        $ticket = $this->tickets->create(['subject' => 'Email issue'])['data'];

        $comm = $this->service->create($ticket['id'], [
            'type' => 'note',
            'content' => 'Called customer back',
            'attachments' => ['file1.pdf'],
        ]);

        $this->assertTrue($comm['success']);
        $this->assertEquals($ticket['id'], $comm['data']['ticket_id']);
        $this->assertEquals('note', $comm['data']['type']);

        $event = $this->db->table('ticket_events')->where('ticket_id', $ticket['id'])->where('event_type', 'communication')->get()->getRowArray();
        $this->assertNotNull($event);
    }

    /** @test */
    public function it_requires_content()
    {
        $ticket = $this->tickets->create(['subject' => 'Missing content'])['data'];

        $this->expectException(\InvalidArgumentException::class);
        $this->service->create($ticket['id'], ['type' => 'note']);
    }
}
