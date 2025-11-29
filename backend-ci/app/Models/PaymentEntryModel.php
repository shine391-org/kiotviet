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
        'mode_of_payment',
        'party_type',
        'party_id',
        'reference_type',
        'reference_id',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'currency',
        'exchange_rate',
        'reference',
        'reference_no',
        'reference_date',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
