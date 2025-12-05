<?php

namespace Tests\Services;

use App\Services\HR\PayrollService;
use App\Services\HR\EmployeeService;
use App\Repositories\HR\PayrollRepository;
use App\Repositories\HR\SalarySlipRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Validators\PayrollValidator;
use App\Validators\EmployeeValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: PayrollService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PayrollServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PayrollService $service;
    private int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $payrollRepo = new PayrollRepository(null, $this->db);
        $slipRepo = new SalarySlipRepository(null, null, $this->db);
        $glRepo = new GLEntryRepository(null, $this->db);
        $empRepo = new EmployeeRepository(null, $this->db);
        $this->service = new PayrollService($payrollRepo, $slipRepo, $empRepo, $glRepo, new PayrollValidator());
        $this->employeeId = (new EmployeeService($empRepo, new EmployeeValidator()))->create(['full_name' => 'Charlie'])['data']['id'];
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_payroll_and_generates_slips_and_gl()
    {
        $res = $this->service->runPayroll([
            'period_start' => '2025-12-01',
            'period_end' => '2025-12-31',
            'employees' => [
                [
                    'employee_id' => $this->employeeId,
                    'earnings' => [
                        ['component_name' => 'Basic', 'amount' => 1000],
                    ],
                    'deductions' => [
                        ['component_name' => 'Tax', 'amount' => 100],
                    ],
                ],
            ],
            'expense_account_id' => 1,
            'payable_account_id' => 2,
        ])['data'];

        $slip = $res['slips'][0];
        $this->assertEquals(900.0, (float) $slip['net_pay']);
        $glCount = $this->db->table('gl_entries')->countAllResults();
        $this->assertEquals(2, $glCount);
    }

    private function seedAccounts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('chart_of_accounts')->insert(['id' => 1, 'name' => 'Salary Expense', 'account_type' => 'expense', 'created_at' => $now]);
        $this->db->table('chart_of_accounts')->insert(['id' => 2, 'name' => 'Salary Payable', 'account_type' => 'liability', 'created_at' => $now]);
    }
}
