<?php

namespace App\Models;

use CodeIgniter\Model;

/** Customer price list mapping. @agent-model: customer_price_lists */
class CustomerPriceListModel extends Model
{
    protected $table = 'customer_price_lists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id','price_list_id','valid_from','valid_to','is_active','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
