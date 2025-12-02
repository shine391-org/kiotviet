<?php

namespace App\Models;

use CodeIgniter\Model;

/** Quotation schema. @agent-model: quotations */
class QuotationModel extends Model
{
    protected $table = 'quotations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'quote_number',
        'opportunity_id',
        'customer_id',
        'lead_id',
        'status',
        'validity_date',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
