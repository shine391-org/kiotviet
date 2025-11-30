<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Employee schema.
 *
 * @agent-model: employees
 * @agent-pattern: CI4 model
 */
class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_code',
        'full_name',
        'branch_id',
        'status',
        'join_date',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
