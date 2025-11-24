<?php

namespace App\Models;

use CodeIgniter\Model;

/** Price list item schema model. @agent-model: price_list_items */
class PriceListItemModel extends Model
{
    protected $table = 'price_list_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'price_list_id','product_id','variant_id',
        'price','discount_percent','discount_amount',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
