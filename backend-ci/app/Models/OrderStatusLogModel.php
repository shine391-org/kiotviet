<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order status log model. @agent-model: order_status_logs */
class OrderStatusLogModel extends Model
{
    protected $table = 'order_status_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'order_id','from_status','to_status','notes','changed_by','changed_at',
        'created_at','updated_at'
    ];
    protected $useTimestamps = false;
}
