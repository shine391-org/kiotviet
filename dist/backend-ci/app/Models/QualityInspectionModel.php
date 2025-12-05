<?php

namespace App\Models;

use CodeIgniter\Model;

/** Quality inspection schema. @agent-model: quality_inspections */
class QualityInspectionModel extends Model
{
    protected $table = 'quality_inspections';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'reference_type',
        'reference_id',
        'status',
        'result',
        'inspected_by',
        'inspected_at',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
