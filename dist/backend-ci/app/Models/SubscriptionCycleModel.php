<?php

namespace App\Models;

use CodeIgniter\Model;

/** Subscription cycle schema. @agent-model: subscription_cycles */
class SubscriptionCycleModel extends Model
{
    protected $table = 'subscription_cycles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'subscription_id',
        'run_date',
        'order_id',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
