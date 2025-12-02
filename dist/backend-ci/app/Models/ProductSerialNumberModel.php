<?php

namespace App\Models;

use CodeIgniter\Model;

/** Product serial number schema model. @agent-model: product_serial_numbers */
class ProductSerialNumberModel extends Model
{
    protected $table = 'product_serial_numbers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'product_id','variant_id','batch_id','serial_number','status',
        'warranty_expiry_date','reserved_for_order_id','reserved_at',
        'sold_to_order_id','sold_date','returned_at','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
