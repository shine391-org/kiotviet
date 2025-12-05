<?php

namespace App\Models;

use CodeIgniter\Model;

/** Project price list mapping. @agent-model: project_price_lists */
class ProjectPriceListModel extends Model
{
    protected $table = 'project_price_lists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'project_id','price_list_id','valid_from','valid_to','is_active','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
