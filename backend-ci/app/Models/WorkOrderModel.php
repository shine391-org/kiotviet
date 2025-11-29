<?php

namespace App\Models;

use CodeIgniter\Model;

/** Work order schema. @agent-model: work_orders */
class WorkOrderModel extends Model
{
    protected $table = 'work_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id',
        'bom_id',
        'branch_id',
        'quantity',
        'status',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
