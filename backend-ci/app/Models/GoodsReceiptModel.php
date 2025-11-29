<?php

namespace App\Models;

use CodeIgniter\Model;

/** Goods receipt schema. @agent-model: goods_receipts */
class GoodsReceiptModel extends Model
{
    protected $table = 'goods_receipts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'receipt_number',
        'purchase_order_id',
        'branch_id',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
