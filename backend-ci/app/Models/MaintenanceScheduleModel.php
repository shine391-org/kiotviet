<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Maintenance schedule schema.
 *
 * @agent-model: maintenance_schedules
 * @agent-pattern: CI4 model
 */
class MaintenanceScheduleModel extends Model
{
    protected $table = 'maintenance_schedules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'asset_id',
        'schedule_name',
        'frequency',
        'next_due_date',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
