<?php

namespace App\Models;

use CodeIgniter\Model;

/** Quality parameter schema. @agent-model: quality_parameters */
class QualityParameterModel extends Model
{
    protected $table = 'quality_parameters';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'uom', 'min_value', 'max_value', 'specification', 'is_active', 'created_at', 'updated_at'];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
