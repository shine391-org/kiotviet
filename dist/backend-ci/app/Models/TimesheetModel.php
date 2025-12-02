<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Timesheet schema.
 *
 * @agent-model: timesheets
 * @agent-pattern: CI4 model
 */
class TimesheetModel extends Model
{
    protected $table = 'timesheets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'timesheet_number',
        'project_id',
        'employee_id',
        'status',
        'total_hours',
        'total_billable',
        'total_cost',
        'notes',
        'created_by',
        'submitted_by',
        'submitted_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
