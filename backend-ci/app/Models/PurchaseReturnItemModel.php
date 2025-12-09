<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PurchaseReturnItemModel - Model for purchase return line items
 * @agent-layer: backend-model
 */
class PurchaseReturnItemModel extends Model
{
    protected $table = 'purchase_return_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'purchase_return_id',
        'product_id',
        'variant_id',
        'product_code',
        'product_name',
        'quantity',
        'import_price',
        'return_price',
        'discount_per_item',
        'amount',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'purchase_return_id' => 'required|integer',
    ];
}
