<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stock entry item schema.
 *
 * @agent-model: stock_entry_items
 * @agent-pattern: CI4 model
 */
class StockEntryItemModel extends Model
{
    protected $table = 'stock_entry_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'stock_entry_id',
        'product_id',
        'variant_id',
        'qty',
        'uom',
        'batch_id',
        'serial_number',
        'source_warehouse_id',
        'target_warehouse_id',
        'source_branch_id',
        'target_branch_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
