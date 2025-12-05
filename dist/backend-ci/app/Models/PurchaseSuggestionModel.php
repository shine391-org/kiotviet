<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase suggestion schema model. @agent-model: purchase_suggestions */
class PurchaseSuggestionModel extends Model
{
    protected $table = 'purchase_suggestions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'reorder_level_id','product_id','variant_id','branch_id','generated_for_date','suggested_qty',
        'on_hand_qty','reserved_qty','available_qty','min_level','max_level','safety_stock',
        'status','reason','purchase_order_id','acknowledged_by','acknowledged_at','converted_at','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
