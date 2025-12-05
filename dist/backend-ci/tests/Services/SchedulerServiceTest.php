<?php

namespace Tests\Services;

use App\Services\Jobs\SchedulerService;
use App\Repositories\Jobs\SchedulerRuleRepository;
use App\Repositories\Jobs\JobRepository;
use App\Validators\SchedulerValidator;
use App\Validators\JobValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: SchedulerService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class SchedulerServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private SchedulerService $service;
    private JobRepository $jobs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->jobs = new JobRepository(null, null, $this->db);
        $this->service = new SchedulerService(
            new SchedulerRuleRepository(null, $this->db),
            $this->jobs,
            new SchedulerValidator(),
            new JobValidator()
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_enqueues_from_due_rules()
    {
        $rule = $this->service->createRule([
            'name' => 'Subscription invoice',
            'cron_expression' => '* * * * *',
            'handler' => 'subscription.generate',
        ])['data'];

        $res = $this->service->tick();
        $this->assertTrue($res['success']);
        $jobs = $this->jobs->list([]);
        $this->assertCount(1, $jobs);
        $this->assertEquals('subscription.generate', $jobs[0]['name']);
    }
}
