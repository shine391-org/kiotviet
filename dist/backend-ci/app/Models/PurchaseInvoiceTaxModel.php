<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase invoice tax schema. @agent-model: purchase_invoice_taxes */
class PurchaseInvoiceTaxModel extends Model
{
    protected $table = 'purchase_invoice_taxes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_id',
        'tax_name',
        'rate_percent',
        'amount',
        'template_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
