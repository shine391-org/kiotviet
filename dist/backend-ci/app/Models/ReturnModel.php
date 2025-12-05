<?php

namespace App\Models;

use CodeIgniter\Model;

/** Return schema model. @agent-model: returns */
class ReturnModel extends Model
{
    protected $table = 'returns';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'return_number',
        'order_id',
        'customer_id',
        'return_amount',
        'refund_shipping_fee',
        'refund_amount',
        'refund_method',
        'reason',
        'reason_detail',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'completed_at',
        'notes',
        'lock_version',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
