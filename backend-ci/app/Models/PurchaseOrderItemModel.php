<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase order item schema. @agent-model: purchase_order_items */
class PurchaseOrderItemModel extends Model
{
    protected $table = 'purchase_order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'received_quantity',
        'rate',
        'amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
