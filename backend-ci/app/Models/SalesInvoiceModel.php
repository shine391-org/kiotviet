<?php

namespace App\Models;

use CodeIgniter\Model;

/** Sales invoice schema. @agent-model: sales_invoices */
class SalesInvoiceModel extends Model
{
    protected $table = 'sales_invoices';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_number',
        'customer_id',
        'posting_date',
        'due_date',
        'status',
        'currency',
        'exchange_rate',
        'total',
        'taxes_total',
        'grand_total',
        'rounding_adjustment',
        'debit_account_id',
        'credit_account_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
