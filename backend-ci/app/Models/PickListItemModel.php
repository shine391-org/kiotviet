<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pick list item schema.
 *
 * @agent-model: pick_list_items
 * @agent-pattern: CI4 model
 */
class PickListItemModel extends Model
{
    protected $table = 'pick_list_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'pick_list_id',
        'stock_entry_item_id',
        'product_id',
        'qty',
        'batch_id',
        'serial_number',
        'source_warehouse_id',
        'target_warehouse_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
