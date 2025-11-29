<?php

namespace App\Models;

use CodeIgniter\Model;

/** Price history model. @agent-model: price_history */
class PriceHistoryModel extends Model
{
    protected $table = 'price_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','source_type','source_id','old_price','new_price','changed_by','changed_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
