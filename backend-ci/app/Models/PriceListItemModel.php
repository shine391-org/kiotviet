<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\ConnectionInterface;

/** Price list item schema model. @agent-model: price_list_items */
class PriceListItemModel extends Model
{
    protected $table = 'price_list_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $DBGroup = 'default';
    protected $allowedFields = [
        'price_list_id','product_id','variant_id',
        'price','discount_percent','discount_amount',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    public function __construct(?ConnectionInterface $db = null)
    {
        if (ENVIRONMENT === 'testing') {
            $this->DBGroup = 'tests';
        }
        parent::__construct($db);
    }
}
