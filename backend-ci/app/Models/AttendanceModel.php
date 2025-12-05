<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Attendance schema.
 *
 * @agent-model: attendances
 * @agent-pattern: CI4 model
 */
class AttendanceModel extends Model
{
    protected $table = 'attendances';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'employee_id',
        'attendance_date',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
