<?php

namespace App\Models;

use CodeIgniter\Model;

/** Invoice-orders junction schema model. @agent-model: invoice_orders */
class InvoiceOrderModel extends Model
{
    protected $table = 'invoice_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'invoice_id',
        'order_id',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
