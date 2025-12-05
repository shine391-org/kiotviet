<?php

namespace App\Models;

use CodeIgniter\Model;

/** Bank reconciliation. @agent-model: bank_reconciliations */
class BankReconciliationModel extends Model
{
    protected $table = 'bank_reconciliations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'bank_statement_id',
        'payment_entry_id',
        'status',
        'matched_amount',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
