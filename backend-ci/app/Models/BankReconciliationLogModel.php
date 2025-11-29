<?php

namespace App\Models;

use CodeIgniter\Model;

/** Bank reconciliation logs. @agent-model: bank_reconciliation_logs */
class BankReconciliationLogModel extends Model
{
    protected $table = 'bank_reconciliation_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'bank_reconciliation_id',
        'action',
        'message',
        'created_at',
    ];
    protected $useSoftDeletes = false;
}
