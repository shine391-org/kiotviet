<?php

namespace App\Models;

use CodeIgniter\Model;

/** Invoice schema model. @agent-model: invoices */
class InvoiceModel extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'invoice_number',
        'invoice_status',
        'invoice_type',
        'e_invoice_status',
        'customer_id',
        'branch_id',
        'issue_date',
        'due_date',
        'subtotal',
        'goods_total',
        'discount_total',
        'net_total',
        'vat_rate',
        'vat_amount',
        'tax_amount',
        'total',
        'other_fee',
        'shipping_fee',
        'customer_payable',
        'customer_paid',
        'cod_amount',
        'rounding_adjustment',
        'payment_status',
        'total_paid',
        'currency_code',
        'exchange_rate',
        'last_payment_date',
        'pdf_path',
        'notes',
        'meta',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
