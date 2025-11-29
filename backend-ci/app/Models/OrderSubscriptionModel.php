<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order subscription schema. @agent-model: order_subscriptions */
class OrderSubscriptionModel extends Model
{
    protected $table = 'order_subscriptions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'template_id',
        'branch_id',
        'payment_method',
        'order_type',
        'next_run_at',
        'last_run_at',
        'frequency_interval',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
