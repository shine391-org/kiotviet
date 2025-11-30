<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Scheduler API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class SchedulerApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_enqueues_and_runs_job_via_api()
    {
        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'name' => 'sub.invoice',
                'cron_expression' => '* * * * *',
                'handler' => 'sub.invoice',
            ]))
            ->post('/api/scheduler-rules')
            ->assertStatus(201);

        $this->withHeaders($this->jsonHeaders())->post('/api/scheduler/tick')->assertStatus(200);
        $run = $this->withHeaders($this->jsonHeaders())->post('/api/jobs/run-next');
        $run->assertStatus(200);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
