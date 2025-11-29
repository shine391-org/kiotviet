<?php

namespace App\Models;

use CodeIgniter\Model;

/** Payment entries schema. @agent-model: payment_entries */
class PaymentEntryModel extends Model
{
    protected $table = 'payment_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'order_id',
        'payment_method',
        'amount',
        'reference',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
