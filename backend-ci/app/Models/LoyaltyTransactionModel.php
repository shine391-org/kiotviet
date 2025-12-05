<?php

namespace App\Models;

use CodeIgniter\Model;

/** Loyalty transaction schema. @agent-model: loyalty_transactions */
class LoyaltyTransactionModel extends Model
{
    protected $table = 'loyalty_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'wallet_id',
        'order_id',
        'points_delta',
        'reason',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
