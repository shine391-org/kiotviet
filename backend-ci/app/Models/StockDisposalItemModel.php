<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stock Disposal Item model.
 * 
 * @agent-model: stock_disposal_items
 */
class StockDisposalItemModel extends Model
{
    protected $table = 'stock_disposal_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'disposal_id',
        'product_id',
        'variant_id',
        'sku',
        'name',
        'quantity',
        'cost_price',
        'disposal_value',
    ];

    protected $validationRules = [
        'disposal_id' => 'required|integer',
        'product_id' => 'required|integer',
        'quantity' => 'required|decimal',
    ];
}
