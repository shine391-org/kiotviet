<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Leave application schema.
 *
 * @agent-model: leave_applications
 * @agent-pattern: CI4 model
 */
class LeaveApplicationModel extends Model
{
    protected $table = 'leave_applications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'total_days',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
