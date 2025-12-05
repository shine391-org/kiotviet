<?php

namespace App\Models;

use CodeIgniter\Model;

/** Loyalty wallet schema. @agent-model: loyalty_wallets */
class LoyaltyWalletModel extends Model
{
    protected $table = 'loyalty_wallets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'points_balance',
        'last_earned_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
