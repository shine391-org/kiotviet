<?php

namespace App\Models;

use CodeIgniter\Model;

/** Quality inspection item schema. @agent-model: quality_inspection_items */
class QualityInspectionItemModel extends Model
{
    protected $table = 'quality_inspection_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'inspection_id',
        'parameter_id',
        'parameter_name',
        'uom',
        'value_numeric',
        'value_text',
        'pass_flag',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
