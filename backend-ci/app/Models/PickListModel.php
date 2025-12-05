<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pick list schema.
 *
 * @agent-model: pick_lists
 * @agent-pattern: CI4 model
 */
class PickListModel extends Model
{
    protected $table = 'pick_lists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'pick_list_number',
        'stock_entry_id',
        'source_warehouse_id',
        'status',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
