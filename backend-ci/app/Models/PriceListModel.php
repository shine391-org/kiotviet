<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\ConnectionInterface;

/** Price list schema model. @agent-model: price_lists */
class PriceListModel extends Model
{
    protected $table = 'price_lists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $DBGroup = 'default';
    protected $allowedFields = [
        'name','type','description','apply_to_groups',
        'start_date','end_date','priority','is_active',
        'formula','base_price_list_id','auto_update','rounding_rule','config',
        'created_at','updated_at','deleted_at',
    ];
    protected $useTimestamps = false;

    public function __construct(?ConnectionInterface $db = null)
    {
        if (ENVIRONMENT === 'testing') {
            $this->DBGroup = 'tests';
        }
        parent::__construct($db);
    }
}
