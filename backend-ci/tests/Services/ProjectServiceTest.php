<?php

namespace Tests\Services;

use App\Services\Projects\ProjectService;
use App\Services\Projects\TaskService;
use App\Repositories\Projects\ProjectRepository;
use App\Repositories\Projects\TaskRepository;
use App\Validators\ProjectValidator;
use App\Validators\TaskValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: ProjectService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class ProjectServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ProjectService $projects;
    private TaskService $tasks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $projectRepo = new ProjectRepository(null, $this->db);
        $taskRepo = new TaskRepository(null, $this->db);
        $this->projects = new ProjectService($projectRepo, $taskRepo, new ProjectValidator());
        $this->tasks = new TaskService($taskRepo, new TaskValidator(), $this->projects);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_progress_from_tasks()
    {
        $project = $this->projects->create(['project_name' => 'Website'])['data'];
        $this->tasks->create(['project_id' => $project['id'], 'task_name' => 'Design', 'progress' => 50]);
        $this->tasks->create(['project_id' => $project['id'], 'task_name' => 'Build', 'progress' => 100]);

        $refetched = $this->projects->show($project['id'])['data'];
        $this->assertEquals(75.0, (float) $refetched['progress']);
    }

    /** @test */
    public function it_updates_project_fields()
    {
        $project = $this->projects->create(['project_name' => 'API'])['data'];
        $updated = $this->projects->update($project['id'], ['project_name' => 'API v2', 'status' => 'in_progress'])['data'];
        $this->assertEquals('API v2', $updated['project_name']);
        $this->assertEquals('in_progress', $updated['status']);
    }
}
