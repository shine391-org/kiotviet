<?php

namespace Tests\Services;

use App\Services\Jobs\JobRunnerService;
use App\Repositories\Jobs\JobRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: JobRunnerService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class JobRunnerServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private JobRunnerService $runner;
    private JobRepository $jobs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->jobs = new JobRepository(null, null, $this->db);
        $this->runner = new JobRunnerService($this->jobs);
        $this->runner->registerHandler('test.success', function () {
            return true;
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_executes_and_marks_success()
    {
        $this->jobs->enqueue([
            'name' => 'test.success',
            'payload' => json_encode([]),
            'next_run_at' => date('Y-m-d H:i:s'),
            'max_attempts' => 3,
        ]);
        $job = $this->runner->runNext();
        $this->assertNotNull($job);
        $this->assertEquals('done', $job['status']);
    }

    /** @test */
    public function it_retries_and_marks_failed_after_max_attempts()
    {
        $this->jobs->enqueue([
            'name' => 'test.fail',
            'payload' => json_encode([]),
            'next_run_at' => date('Y-m-d H:i:s'),
            'max_attempts' => 1,
        ]);
        $job = $this->runner->runNext();
        $this->assertEquals('failed', $job['status']);
    }
}
