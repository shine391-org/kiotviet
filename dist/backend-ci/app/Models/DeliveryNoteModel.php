<?php

namespace App\Models;

use CodeIgniter\Model;

/** Delivery note schema model. @agent-model: delivery_notes */
class DeliveryNoteModel extends Model
{
    protected $table = 'delivery_notes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'delivery_number','order_id','customer_id','branch_id',
        'delivery_date','expected_delivery_date','status',
        'shipping_address','tracking_number','carrier','notes',
        'confirmed_by','confirmed_at','delivered_by','delivered_at',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
