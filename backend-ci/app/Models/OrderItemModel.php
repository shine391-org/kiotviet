<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order item schema model. @agent-model: order_items */
class OrderItemModel extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'order_id','product_id','variant_id','batch_id','serial_numbers','quantity',
        'base_price','final_price','price_list_id','price_list_name',
        'created_at','updated_at','deleted_at',
    ];
    protected $useTimestamps = false;
}
