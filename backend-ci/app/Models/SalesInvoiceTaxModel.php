<?php

namespace App\Models;

use CodeIgniter\Model;

/** Sales invoice tax schema. @agent-model: sales_invoice_taxes */
class SalesInvoiceTaxModel extends Model
{
    protected $table = 'sales_invoice_taxes';
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
