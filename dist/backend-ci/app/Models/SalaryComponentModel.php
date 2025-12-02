<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Salary component schema.
 *
 * @agent-model: salary_components
 * @agent-pattern: CI4 model
 */
class SalaryComponentModel extends Model
{
    protected $table = 'salary_components';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'salary_slip_id',
        'component_name',
        'component_type',
        'amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
