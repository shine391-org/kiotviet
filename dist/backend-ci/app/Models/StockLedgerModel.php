<?php

namespace App\Models;

use CodeIgniter\Model;

/** Stock ledger model. @agent-model: stock_ledgers */
class StockLedgerModel extends Model
{
    protected $table = 'stock_ledgers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','branch_id','warehouse_id','batch_id','serial_number',
        'movement_date','reference_type','reference_id','reference_seq','qty_delta',
        'unit_cost','total_cost','created_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
