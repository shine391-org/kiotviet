<?php

namespace App\Models;

use CodeIgniter\Model;

/** Sales invoice item schema. @agent-model: sales_invoice_items */
class SalesInvoiceItemModel extends Model
{
    protected $table = 'sales_invoice_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'rate',
        'amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
