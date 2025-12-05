<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Salary slip schema.
 *
 * @agent-model: salary_slips
 * @agent-pattern: CI4 model
 */
class SalarySlipModel extends Model
{
    protected $table = 'salary_slips';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'payroll_entry_id',
        'employee_id',
        'status',
        'period_start',
        'period_end',
        'total_earnings',
        'total_deductions',
        'net_pay',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
