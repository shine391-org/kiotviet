<?php

namespace App\Models;

use CodeIgniter\Model;

/** Purchase invoice schema. @agent-model: purchase_invoices */
class PurchaseInvoiceModel extends Model
{
    protected $table = 'purchase_invoices';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_number',
        'supplier_id',
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
