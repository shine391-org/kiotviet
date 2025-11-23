<?php

namespace App\Models;

use CodeIgniter\Model;

/** Price list schema model. @agent-model: price_lists */
class PriceListModel extends Model
{
    protected $table = 'price_lists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name','type','description','apply_to_groups',
        'start_date','end_date','priority','is_active',
        'created_at','updated_at','deleted_at',
    ];
    protected $useTimestamps = false;
}
