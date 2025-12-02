<?php

namespace App\Services\HR;

use App\Repositories\HR\PayrollRepository;
use App\Repositories\HR\SalarySlipRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Validators\PayrollValidator;
use RuntimeException;

/**
 * Payroll orchestration.
 *
 * @agent-service: Payroll
 * @agent-pattern: Generate salary slips + GL
 * @agent-reusable: MEDIUM
 */
class PayrollService
{
    protected PayrollRepository $payrolls;
    protected SalarySlipRepository $slips;
    protected EmployeeRepository $employees;
    protected GLEntryRepository $gl;
    protected PayrollValidator $validator;

    public function __construct(
        ?PayrollRepository $payrolls = null,
        ?SalarySlipRepository $slips = null,
        ?EmployeeRepository $employees = null,
        ?GLEntryRepository $gl = null,
        ?PayrollValidator $validator = null
    ) {
        $this->payrolls = $payrolls ?? new PayrollRepository();
        $this->slips = $slips ?? new SalarySlipRepository();
        $this->employees = $employees ?? new EmployeeRepository();
        $this->gl = $gl ?? new GLEntryRepository();
        $this->validator = $validator ?? new PayrollValidator();
    }

    /**
     * Run payroll and generate salary slips.
     *
     * @agent-use: POST /api/payroll
     * @agent-pattern: Batch create with totals
     */
    public function runPayroll(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $number = $this->payrolls->nextNumber();
        $entry = $this->payrolls->create([
            'payroll_number' => $number,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'submitted',
        ]);

        $slips = [];
        foreach ($data['employees'] as $emp) {
            $employee = $this->requireEmployee((int) $emp['employee_id']);
            $totals = $this->totals($emp['earnings'], $emp['deductions']);
            $slipRow = [
                'payroll_entry_id' => $entry['id'],
                'employee_id' => $employee['id'],
                'status' => 'submitted',
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_earnings' => $totals['earnings'],
                'total_deductions' => $totals['deductions'],
                'net_pay' => $totals['net'],
            ];
            $components = array_merge($emp['earnings'], $emp['deductions']);
            $slips[] = $this->slips->create($slipRow, $components);
        }

        $expenseAccount = $input['expense_account_id'] ?? 1;
        $payableAccount = $input['payable_account_id'] ?? 2;
        $this->postGl($entry, $slips, $expenseAccount, $payableAccount);

        return ['success' => true, 'data' => ['payroll' => $entry, 'slips' => $slips]];
    }

    /** @agent-use: GET /api/payroll/(:num) */
    public function show(int $id): array
    {
        $entry = $this->payrolls->find($id);
        if (! $entry) {
            throw new RuntimeException('Payroll entry not found');
        }
        $slips = $this->slips->list(['payroll_entry_id' => $id]);
        return ['success' => true, 'data' => ['payroll' => $entry, 'slips' => $slips]];
    }

    private function totals(array $earnings, array $deductions): array
    {
        $earn = array_sum(array_map(fn ($c) => (float) $c['amount'], $earnings));
        $ded = array_sum(array_map(fn ($c) => (float) $c['amount'], $deductions));
        return ['earnings' => $earn, 'deductions' => $ded, 'net' => $earn - $ded];
    }

    private function postGl(array $entry, array $slips, int $expenseAccount, int $payableAccount): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($slips as $slip) {
            $rows[] = [
                'account_id' => $expenseAccount,
                'posting_date' => $entry['period_end'],
                'debit' => $slip['total_earnings'],
                'credit' => 0,
                'reference_type' => 'payroll_entry',
                'reference_id' => $entry['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rows[] = [
                'account_id' => $payableAccount,
                'posting_date' => $entry['period_end'],
                'debit' => 0,
                'credit' => $slip['total_earnings'],
                'reference_type' => 'payroll_entry',
                'reference_id' => $entry['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
            $db->transStart();
            $db->table('gl_entries')->insertBatch($rows);
            $db->transComplete();
        }
    }

    private function requireEmployee(int $id): array
    {
        $row = $this->employees->find($id);
        if (! $row) {
            throw new RuntimeException('Employee not found');
        }
        return $row;
    }
}
