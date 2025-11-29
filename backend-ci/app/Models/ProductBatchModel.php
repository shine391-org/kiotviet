<?php

namespace App\Models;

use CodeIgniter\Model;

/** Product batch schema model. @agent-model: product_batches */
class ProductBatchModel extends Model
{
    protected $table = 'product_batches';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','branch_id','warehouse_id','batch_number',
        'manufacture_date','expiry_date','initial_quantity','current_quantity',
        'cost_per_unit','supplier_name','reference_document','status',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
