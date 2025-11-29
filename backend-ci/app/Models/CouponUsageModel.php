<?php

namespace App\Models;

use CodeIgniter\Model;

/** Coupon usage schema. @agent-model: coupon_usages */
class CouponUsageModel extends Model
{
    protected $table = 'coupon_usages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'coupon_id',
        'order_id',
        'customer_id',
        'used_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
