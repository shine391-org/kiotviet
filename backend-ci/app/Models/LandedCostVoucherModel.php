<?php

namespace App\Models;

use CodeIgniter\Model;

/** Landed cost voucher schema. @agent-model: landed_cost_vouchers */
class LandedCostVoucherModel extends Model
{
    protected $table = 'landed_cost_vouchers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'voucher_number',
        'goods_receipt_id',
        'total_cost',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
