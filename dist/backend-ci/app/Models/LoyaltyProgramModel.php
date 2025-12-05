<?php

namespace App\Models;

use CodeIgniter\Model;

/** Loyalty program schema. @agent-model: loyalty_programs */
class LoyaltyProgramModel extends Model
{
    protected $table = 'loyalty_programs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'customer_group_id',
        'earn_rate',
        'redeem_rate',
        'expiry_days',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
