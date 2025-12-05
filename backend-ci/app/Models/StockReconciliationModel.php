<?php

namespace App\Models;

use CodeIgniter\Model;

/** Stock reconciliation model. @agent-model: stock_reconciliations */
class StockReconciliationModel extends Model
{
    protected $table = 'stock_reconciliations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'recon_number','branch_id','status','notes','created_by','created_at','updated_at','approved_by','approved_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
