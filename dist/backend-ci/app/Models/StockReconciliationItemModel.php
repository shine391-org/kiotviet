<?php

namespace App\Models;

use CodeIgniter\Model;

/** Stock reconciliation item model. @agent-model: stock_reconciliation_items */
class StockReconciliationItemModel extends Model
{
    protected $table = 'stock_reconciliation_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'reconciliation_id','product_id','variant_id','batch_id',
        'counted_qty','current_qty','variance_qty','unit_cost','remarks',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
