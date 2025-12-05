<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase invoice item schema. @agent-model: purchase_invoice_items */
class PurchaseInvoiceItemModel extends Model
{
    protected $table = 'purchase_invoice_items';
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
