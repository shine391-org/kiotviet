<?php

namespace App\Models;

use CodeIgniter\Model;

/** Credit limit schema. @agent-model: credit_limits */
class CreditLimitModel extends Model
{
    protected $table = 'credit_limits';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'limit_amount',
        'on_hold',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
