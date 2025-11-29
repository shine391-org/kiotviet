<?php

namespace App\Models;

use CodeIgniter\Model;

/** Stock bin model. @agent-model: stock_bins */
class StockBinModel extends Model
{
    protected $table = 'stock_bins';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','branch_id','batch_id','on_hand_qty','reserved_qty','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
