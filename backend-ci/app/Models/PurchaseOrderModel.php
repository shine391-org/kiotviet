<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase order schema. @agent-model: purchase_orders */
class PurchaseOrderModel extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'po_number',
        'code',
        'branch_id',
        'payment_method',
        'total',
        'status',
        'received_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
