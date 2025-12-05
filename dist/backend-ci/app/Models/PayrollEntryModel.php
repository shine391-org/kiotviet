<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Payroll entry schema.
 *
 * @agent-model: payroll_entries
 * @agent-pattern: CI4 model
 */
class PayrollEntryModel extends Model
{
    protected $table = 'payroll_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'payroll_number',
        'period_start',
        'period_end',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
