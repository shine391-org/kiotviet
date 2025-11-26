<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order schema model. @agent-model: orders */
class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'order_number','customer_id','customer_group_id','branch_id','order_date','status','order_type',
        'payment_method','shipping_fee','subtotal','discount_total','total',
        'paid_amount','debt_amount','payment_status','is_paid','applied_price_list_id',
        'shipping_name','shipping_phone','shipping_address','shipping_ward','shipping_district','shipping_city',
        'notes','confirmed_at','processing_at','shipping_at','delivered_at','completed_at','cancelled_at','cancellation_reason','cod_collected',
        'created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
