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
 * @agent-test: TaskService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class TaskServiceTest extends CIUnitTestCase
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
    public function it_updates_task_status_and_project_progress()
    {
        $project = $this->projects->create(['project_name' => 'Mobile App'])['data'];
        $task = $this->tasks->create(['project_id' => $project['id'], 'task_name' => 'Implement login'])['data'];
        $updated = $this->tasks->updateStatus($task['id'], ['status' => 'completed'])['data'];

        $this->assertEquals('completed', $updated['status']);
        $this->assertEquals(100.0, (float) $updated['progress']);

        $projectAfter = $this->projects->show($project['id'])['data'];
        $this->assertEquals(100.0, (float) $projectAfter['progress']);
    }
}
