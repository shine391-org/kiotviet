<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Packing slip schema.
 *
 * @agent-model: packing_slips
 * @agent-pattern: CI4 model
 */
class PackingSlipModel extends Model
{
    protected $table = 'packing_slips';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'packing_slip_number',
        'pick_list_id',
        'stock_entry_id',
        'status',
        'source_warehouse_id',
        'target_warehouse_id',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
