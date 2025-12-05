<?php

namespace Tests\Services;

use App\Services\Projects\TimesheetService;
use App\Services\Projects\ProjectService;
use App\Services\Projects\TaskService;
use App\Repositories\Projects\ProjectRepository;
use App\Repositories\Projects\TaskRepository;
use App\Repositories\Projects\TimesheetRepository;
use App\Repositories\Projects\ActivityTypeRepository;
use App\Validators\ProjectValidator;
use App\Validators\TaskValidator;
use App\Validators\TimesheetValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: TimesheetService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class TimesheetServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private TimesheetService $timesheets;
    private TaskService $tasks;
    private ProjectService $projects;
    private ActivityTypeRepository $activities;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $projectRepo = new ProjectRepository(null, $this->db);
        $taskRepo = new TaskRepository(null, $this->db);
        $timesheetRepo = new TimesheetRepository(null, null, $this->db);
        $this->activities = new ActivityTypeRepository(null, $this->db);
        $this->projects = new ProjectService($projectRepo, $taskRepo, new ProjectValidator());
        $this->tasks = new TaskService($taskRepo, new TaskValidator(), $this->projects);
        $this->timesheets = new TimesheetService(
            $timesheetRepo,
            $this->activities,
            $taskRepo,
            new TimesheetValidator()
        );
        $this->seedBase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_timesheet_with_totals_and_updates_task_hours()
    {
        $project = $this->projects->create(['project_name' => 'ERP Implementation'])['data'];
        $task = $this->tasks->create(['project_id' => $project['id'], 'task_name' => 'Analysis'])['data'];
        $activity = $this->activities->create(['activity_name' => 'Development', 'billing_rate' => 50, 'cost_rate' => 30]);

        $ts = $this->timesheets->create([
            'project_id' => $project['id'],
            'employee_id' => 5,
            'items' => [
                ['task_id' => $task['id'], 'activity_type_id' => $activity['id'], 'hours' => 2.5],
                ['project_id' => $project['id'], 'hours' => 1.0, 'billing_rate' => 60, 'cost_rate' => 25],
            ],
        ])['data'];

        $this->assertEquals(3.5, (float) $ts['total_hours']);
        $this->assertEquals(2.5 * 50 + 1 * 60, (float) $ts['total_billable']);
        $this->assertEquals(2.5 * 30 + 1 * 25, (float) $ts['total_cost']);
        $taskAfter = $this->tasks->show($task['id'])['data'];
        $this->assertEquals(2.5, (float) $taskAfter['actual_hours']);
    }

    /** @test */
    public function it_submits_timesheet()
    {
        $ts = $this->timesheets->create([
            'items' => [
                ['hours' => 1.0],
            ],
        ])['data'];

        $submitted = $this->timesheets->submit($ts['id'], ['submitted_by' => 9])['data'];
        $this->assertEquals('submitted', $submitted['status']);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('activity_types')->insert([
            'activity_name' => 'Default',
            'billing_rate' => 0,
            'cost_rate' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
