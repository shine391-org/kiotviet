<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS shift payment log. @agent-model: pos_shift_payments */
class POSShiftPaymentModel extends Model
{
    protected $table = 'pos_shift_payments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'shift_id',
        'order_id',
        'payment_method',
        'amount',
        'reference_type',
        'reference_id',
        'created_at',
        'updated_at',
    ];
}
