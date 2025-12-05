<?php

namespace App\Models;

use CodeIgniter\Model;

/** Subcontracting material schema. @agent-model: subcontracting_materials */
class SubcontractingMaterialModel extends Model
{
    protected $table = 'subcontracting_materials';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'subcontracting_order_id',
        'material_product_id',
        'quantity',
        'issued_quantity',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
