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
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'purchase_return_id' => 'required|integer',
        'quantity' => 'required|integer|greater_than[0]',
        'return_price' => 'required|numeric|greater_than_equal_to[0]',
        'amount' => 'required|numeric|greater_than_equal_to[0]',
    ];
}
