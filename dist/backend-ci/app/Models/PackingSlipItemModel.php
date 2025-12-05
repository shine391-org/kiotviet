<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Packing slip item schema.
 *
 * @agent-model: packing_slip_items
 * @agent-pattern: CI4 model
 */
class PackingSlipItemModel extends Model
{
    protected $table = 'packing_slip_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'packing_slip_id',
        'pick_list_item_id',
        'stock_entry_item_id',
        'product_id',
        'qty',
        'batch_id',
        'serial_number',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
