<?php

namespace App\Models;

use CodeIgniter\Model;

/** Order template item schema. @agent-model: order_template_items */
class OrderTemplateItemModel extends Model
{
    protected $table = 'order_template_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'template_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
