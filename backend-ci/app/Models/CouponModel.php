<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table = 'coupons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'code', 'discount_type', 'discount_value', 'min_amount',
        'expiry_date', 'usage_limit', 'used_count', 'status',
    ];
    protected $useTimestamps = true;
}
