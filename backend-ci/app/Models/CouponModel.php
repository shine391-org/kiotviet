<?php

namespace App\Models;

use CodeIgniter\Model;

/** Coupon schema. @agent-model: coupons */
class CouponModel extends Model
{
    protected $table = 'coupons';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'code',
        'discount_type',
        'discount_value',
        'min_amount',
        'expiry_date',
        'usage_limit',
        'used_count',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
