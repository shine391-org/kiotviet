<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stock entry schema.
 *
 * @agent-model: stock_entries
 * @agent-pattern: CI4 model
 */
class StockEntryModel extends Model
{
    protected $table = 'stock_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'entry_number',
        'type',
        'status',
        'branch_id',
        'source_warehouse_id',
        'target_warehouse_id',
        'reference_type',
        'reference_id',
        'return_reason',
        'created_by',
        'submitted_by',
        'cancelled_by',
        'created_at',
        'updated_at',
        'submitted_at',
        'cancelled_at',
    ];
    protected $useSoftDeletes = false;
}
