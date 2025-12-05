<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Leave type schema.
 *
 * @agent-model: leave_types
 * @agent-pattern: CI4 model
 */
class LeaveTypeModel extends Model
{
    protected $table = 'leave_types';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'leave_name',
        'default_allocation',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
