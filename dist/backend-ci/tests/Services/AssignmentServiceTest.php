<?php

namespace Tests\Services;

use App\Services\Assignments\AssignmentService;
use App\Repositories\Assignments\AssignmentRepository;
use App\Validators\AssignmentValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: AssignmentService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AssignmentServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new AssignmentRepository(null, null, $this->db);
        $this->service = new AssignmentService($repo, new AssignmentValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_assigns_round_robin()
    {
        $this->service->createRule([
            'name' => 'Ticket assign',
            'entity_type' => 'ticket',
            'team_members' => [1, 2, 3],
        ]);

        $a1 = $this->service->assign(['entity_type' => 'ticket', 'entity_id' => 10])['data']['assignee_id'];
        $a2 = $this->service->assign(['entity_type' => 'ticket', 'entity_id' => 11])['data']['assignee_id'];
        $a3 = $this->service->assign(['entity_type' => 'ticket', 'entity_id' => 12])['data']['assignee_id'];

        $this->assertEquals(1, $a1);
        $this->assertEquals(2, $a2);
        $this->assertEquals(3, $a3);
    }
}
