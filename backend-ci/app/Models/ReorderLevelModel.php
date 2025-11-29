<?php

namespace App\Models;

use CodeIgniter\Model;

/** Reorder level schema model. @agent-model: reorder_levels */
class ReorderLevelModel extends Model
{
    protected $table = 'reorder_levels';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','branch_id','min_level','max_level','safety_stock','is_active','created_at','updated_at','deleted_at',
    ];
    protected $useSoftDeletes = true;
    protected $useTimestamps = false;
}
