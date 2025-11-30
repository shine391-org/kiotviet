<?php

namespace Tests\Services;

use App\Services\HR\LeaveService;
use App\Services\HR\EmployeeService;
use App\Repositories\HR\LeaveRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Validators\LeaveValidator;
use App\Validators\EmployeeValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: LeaveService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class LeaveServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private LeaveService $service;
    private EmployeeService $employees;
    private int $leaveTypeId;
    private int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $leaveRepo = new LeaveRepository(null, null, $this->db);
        $empRepo = new EmployeeRepository(null, $this->db);
        $this->service = new LeaveService($leaveRepo, $empRepo, new LeaveValidator());
        $this->employees = new EmployeeService($empRepo, new EmployeeValidator());
        $this->seed();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_and_approves_leave_and_updates_balance()
    {
        $apply = $this->service->apply([
            'employee_id' => $this->employeeId,
            'leave_type_id' => $this->leaveTypeId,
            'from_date' => '2025-12-01',
            'to_date' => '2025-12-02',
            'reason' => 'Personal',
        ])['data'];
        $this->assertEquals('pending', $apply['status']);

        $balanceAfterApply = $this->service->balance($this->employeeId, $this->leaveTypeId);
        $this->assertEquals(12.0, $balanceAfterApply);

        $approved = $this->service->approve($apply['id'], ['approved_by' => 1])['data'];
        $this->assertEquals('approved', $approved['status']);

        $balanceAfterApprove = $this->service->balance($this->employeeId, $this->leaveTypeId);
        $this->assertEquals(10.0, $balanceAfterApprove);
    }

    private function seed(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('leave_types')->insert([
            'id' => 1,
            'leave_name' => 'Annual',
            'default_allocation' => 12,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->leaveTypeId = 1;
        $emp = $this->employees->create(['full_name' => 'Alice'])['data'];
        $this->employeeId = $emp['id'];
    }
}
