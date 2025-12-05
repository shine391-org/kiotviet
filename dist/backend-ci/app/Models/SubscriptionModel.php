<?php

namespace App\Models;

use CodeIgniter\Model;

/** Subscription schema. @agent-model: subscriptions */
class SubscriptionModel extends Model
{
    protected $table = 'subscriptions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'template_id',
        'plan_name',
        'interval_days',
        'next_run_at',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
