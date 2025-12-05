<?php

namespace App\Models;

use CodeIgniter\Model;

/** Goods receipt item schema. @agent-model: goods_receipt_items */
class GoodsReceiptItemModel extends Model
{
    protected $table = 'goods_receipt_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'goods_receipt_id',
        'product_id',
        'quantity',
        'rate',
        'amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
