<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Depreciation schedule schema.
 *
 * @agent-model: depreciation_schedules
 * @agent-pattern: CI4 model
 */
class DepreciationScheduleModel extends Model
{
    protected $table = 'depreciation_schedules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'asset_id',
        'method',
        'rate',
        'start_date',
        'total_periods',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
