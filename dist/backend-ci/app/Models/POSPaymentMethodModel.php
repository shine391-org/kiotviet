<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS profile payment methods. @agent-model: pos_payment_methods */
class POSPaymentMethodModel extends Model
{
    protected $table = 'pos_payment_methods';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'profile_id',
        'payment_method',
        'is_allowed',
        'created_at',
        'updated_at',
    ];
}
