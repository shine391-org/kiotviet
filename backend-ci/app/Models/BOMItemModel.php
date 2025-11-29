<?php

namespace App\Models;

use CodeIgniter\Model;

/** BOM item schema. @agent-model: bom_items */
class BOMItemModel extends Model
{
    protected $table = 'bom_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['bom_id', 'component_product_id', 'quantity', 'uom', 'scrap_percent', 'created_at', 'updated_at'];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
