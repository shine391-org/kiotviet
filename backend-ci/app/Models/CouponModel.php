<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table = 'coupons';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    
    // Removed sensitive fields: used_count, creator_id, branch_id, customer_group_id
    // These must be set programmatically in services after proper authorization
    protected $allowedFields = [
        'name', 'code', 'discount_type', 'discount_value', 'min_amount',
        'start_date', 'expiry_date', 'validity_type', 'validity_period',
        'usage_limit', 'status', 'description', 'is_combinable',
    ];
    
    protected $useTimestamps = true;
    
    protected $validationRules = [
        'name'            => 'permit_empty|max_length[255]',
        'code'            => 'required|max_length[50]',
        'discount_type'   => 'required|in_list[percentage,fixed,percent]',
        'discount_value'  => 'required|decimal|greater_than[0]',
        'min_amount'      => 'permit_empty|decimal|greater_than_equal_to[0]',
        'start_date'      => 'permit_empty|valid_date',
        'expiry_date'     => 'permit_empty|valid_date',
        'validity_type'   => 'permit_empty|in_list[date_range,period,fixed,rolling]',
        'validity_period' => 'permit_empty|integer|greater_than[0]',
        'usage_limit'     => 'permit_empty|integer|greater_than_equal_to[0]',
        'status'          => 'required|in_list[active,inactive,expired]',
        'description'     => 'permit_empty|max_length[1000]',
        'is_combinable'   => 'permit_empty|in_list[0,1]',
    ];
    
    protected $validationMessages = [
        'code' => [
            'required' => 'Mã voucher là bắt buộc',
            'max_length' => 'Mã voucher không được quá 50 ký tự',
        ],
        'discount_type' => [
            'required' => 'Loại giảm giá là bắt buộc',
            'in_list' => 'Loại giảm giá không hợp lệ',
        ],
        'discount_value' => [
            'required' => 'Giá trị giảm giá là bắt buộc',
            'greater_than' => 'Giá trị giảm giá phải lớn hơn 0',
        ],
        'status' => [
            'required' => 'Trạng thái là bắt buộc',
            'in_list' => 'Trạng thái không hợp lệ',
        ],
    ];
}
