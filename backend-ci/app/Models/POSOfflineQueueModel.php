<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS offline queue schema. @agent-model: pos_offline_queue */
class POSOfflineQueueModel extends Model
{
    protected $table = 'pos_offline_queue';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'temp_id',
        'device_id',
        'idempotency_key',
        'user_id',
        'branch_id',
        'payload',
        'status',
        'order_id',
        'error_message',
        'created_at',
        'synced_at',
        'updated_at',
    ];
}
