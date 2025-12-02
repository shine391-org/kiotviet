<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Depreciation schedule line schema.
 *
 * @agent-model: depreciation_schedule_lines
 * @agent-pattern: CI4 model
 */
class DepreciationScheduleLineModel extends Model
{
    protected $table = 'depreciation_schedule_lines';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'schedule_id',
        'period_no',
        'posting_date',
        'amount',
        'posted_gl_entry_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
