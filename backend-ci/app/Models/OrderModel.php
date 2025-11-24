<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order schema model. @agent-model: orders */
class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'customer_id','customer_group_id','order_date','status',
        'subtotal','discount_total','total','applied_price_list_id',
        'created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
