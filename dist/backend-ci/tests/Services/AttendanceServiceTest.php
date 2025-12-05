<?php

namespace Tests\Services;

use App\Services\HR\AttendanceService;
use App\Services\HR\EmployeeService;
use App\Repositories\HR\AttendanceRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Validators\EmployeeValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: AttendanceService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AttendanceServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private AttendanceService $service;
    private int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new AttendanceRepository(null, $this->db);
        $empRepo = new EmployeeRepository(null, $this->db);
        $this->service = new AttendanceService($repo, $empRepo);
        $this->employeeId = (new EmployeeService($empRepo, new EmployeeValidator()))->create(['full_name' => 'Bob'])['data']['id'];
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_attendance()
    {
        $logged = $this->service->log([
            'employee_id' => $this->employeeId,
            'attendance_date' => '2025-12-01',
            'status' => 'present',
        ])['data'];

        $this->assertEquals('present', $logged['status']);
    }
}
