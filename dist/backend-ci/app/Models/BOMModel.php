<?php

namespace App\Models;

use CodeIgniter\Model;

/** Bill of materials schema. @agent-model: bill_of_materials */
class BOMModel extends Model
{
    protected $table = 'bill_of_materials';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['product_id', 'version', 'quantity', 'uom', 'cost', 'is_active', 'created_at', 'updated_at'];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
