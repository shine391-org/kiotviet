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
        'customer_id',
        'branch_id',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'pdf_path',
        'notes',
        'meta',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
