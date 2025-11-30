<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Project/Timesheet API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class ProjectTimesheetApiTest extends CIUnitTestCase
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
    public function it_runs_project_task_timesheet_flow()
    {
        $projectRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['project_name' => 'New Project']))
            ->post('/api/projects');
        $projectRes->assertStatus(201);
        $project = $this->getJsonFromResponse($projectRes)['data'];

        $taskRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['project_id' => $project['id'], 'task_name' => 'Plan']))
            ->post('/api/tasks');
        $taskRes->assertStatus(201);
        $task = $this->getJsonFromResponse($taskRes)['data'];

        $tsRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'project_id' => $project['id'],
                'items' => [
                    ['task_id' => $task['id'], 'hours' => 2, 'billing_rate' => 40, 'cost_rate' => 20],
                ],
            ]))
            ->post('/api/timesheets');
        $tsRes->assertStatus(201);

        $summaryRes = $this->withHeaders($this->jsonHeaders())
            ->get('/api/projects/' . $project['id'] . '/timesheets/summary');
        $summaryRes->assertStatus(200);
        $summary = $this->getJsonFromResponse($summaryRes)['data'];
        $this->assertEquals(2.0, (float) $summary['total_hours']);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
