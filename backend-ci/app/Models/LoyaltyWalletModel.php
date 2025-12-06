<?php

namespace App\Models;

use CodeIgniter\Model;

class LoyaltyWalletModel extends Model
{
    protected $table = 'loyalty_wallets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id', 'points_balance', 'last_earned_at',
    ];
    protected $useTimestamps = true;
}
