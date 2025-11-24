<?php

namespace App\Models;

use CodeIgniter\Model;

/** Inventory movement model. @agent-model: inventory_movements */
class InventoryMovementModel extends Model
{
    protected $table = 'inventory_movements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'branch_id','product_id','variant_id','type','quantity',
        'reference_type','reference_id','notes','created_by','created_at','updated_at'
    ];
    protected $useTimestamps = false;
}
