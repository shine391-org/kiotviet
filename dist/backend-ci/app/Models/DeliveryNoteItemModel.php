<?php

namespace App\Models;

use CodeIgniter\Model;

/** Delivery note item schema model. @agent-model: delivery_note_items */
class DeliveryNoteItemModel extends Model
{
    protected $table = 'delivery_note_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'delivery_note_id','order_item_id','product_id','variant_id','batch_id','serial_number',
        'quantity','delivered_quantity','notes','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
