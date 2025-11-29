<?php

namespace App\Models;

use CodeIgniter\Model;

/** Subcontracting order schema. @agent-model: subcontracting_orders */
class SubcontractingOrderModel extends Model
{
    protected $table = 'subcontracting_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_number',
        'supplier_id',
        'product_id',
        'quantity',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
