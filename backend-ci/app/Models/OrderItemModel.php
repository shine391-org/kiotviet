<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order item schema model. @agent-model: order_items */
class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_id','product_id','variant_id','quantity',
        'base_price','final_price','price_list_id','price_list_name',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
