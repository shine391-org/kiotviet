<?php

namespace App\Models;

use CodeIgniter\Model;

/** Delivery note item schema model. @agent-model: delivery_note_items */
class DeliveryNoteItemModel extends Model
{
    protected $table = 'delivery_note_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'delivery_note_id','order_item_id','product_id','variant_id','batch_id','serial_number',
        'quantity','delivered_quantity','notes','created_at','updated_at','deleted_at',
    ];
    protected $useTimestamps = false;
}
