<?php

namespace App\Models;

use CodeIgniter\Model;

/** Payment entry allocations. @agent-model: payment_entry_allocations */
class PaymentEntryAllocationModel extends Model
{
    protected $table = 'payment_entry_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'payment_entry_id',
        'reference_type',
        'reference_id',
        'allocated_amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
