<?php

namespace App\Models;

use CodeIgniter\Model;

/** Landed cost item schema. @agent-model: landed_cost_items */
class LandedCostItemModel extends Model
{
    protected $table = 'landed_cost_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'landed_cost_voucher_id',
        'goods_receipt_item_id',
        'cost_component',
        'amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
