<?php

namespace App\Models;

use CodeIgniter\Model;

/** Quotation item schema. @agent-model: quotation_items */
class QuotationItemModel extends Model
{
    protected $table = 'quotation_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'quotation_id',
        'product_id',
        'quantity',
        'price',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
