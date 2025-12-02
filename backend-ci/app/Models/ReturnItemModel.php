<?php

namespace App\Models;

use CodeIgniter\Model;

/** Return items schema model. @agent-model: return_items */
class ReturnItemModel extends Model
{
    protected $table = 'return_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'return_id',
        'order_item_id',
        'quantity_returned',
        'item_condition',
        'deleted_at',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
