<?php

namespace App\Models;

use CodeIgniter\Model;

/** Return items schema model. @agent-model: return_items */
class ReturnItemModel extends Model
{
    protected $table = 'return_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'return_id',
        'order_item_id',
        'quantity_returned',
        'item_condition',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
