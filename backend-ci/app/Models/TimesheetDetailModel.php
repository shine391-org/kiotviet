<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Timesheet detail schema.
 *
 * @agent-model: timesheet_details
 * @agent-pattern: CI4 model
 */
class TimesheetDetailModel extends Model
{
    protected $table = 'timesheet_details';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'timesheet_id',
        'project_id',
        'task_id',
        'activity_type_id',
        'work_date',
        'hours',
        'billing_rate',
        'cost_rate',
        'billable_amount',
        'cost_amount',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
