<?php

namespace Tests\Services;

use App\Services\Notifications\NotificationService;
use App\Repositories\Notifications\NotificationRuleRepository;
use App\Validators\NotificationValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: NotificationService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class NotificationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new NotificationRuleRepository(null, null, $this->db);
        $this->service = new NotificationService($repo, new NotificationValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_triggers_notifications_for_event()
    {
        $this->service->createRule([
            'name' => 'Ticket created',
            'event_type' => 'ticket.created',
            'template' => 'Ticket {{id}}',
        ]);
        $res = $this->service->trigger([
            'event_type' => 'ticket.created',
            'entity_type' => 'ticket',
            'entity_id' => 10,
            'payload' => ['id' => 10],
        ]);
        $this->assertTrue($res['success']);
        $this->assertCount(1, $res['data']);
    }
}
