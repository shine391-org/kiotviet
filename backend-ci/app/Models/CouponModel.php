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
        'name', 'code', 'discount_type', 'discount_value', 'min_amount',
        'start_date', 'expiry_date', 'validity_type', 'validity_period',
        'usage_limit', 'used_count', 'status', 'description',
        'branch_id', 'customer_group_id', 'creator_id', 'is_combinable',
    ];
    protected $useTimestamps = true;
}
