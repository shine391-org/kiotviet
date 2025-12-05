<?php

namespace App\Models;

use CodeIgniter\Model;

/** Bank statement lines. @agent-model: bank_statements */
class BankStatementModel extends Model
{
    protected $table = 'bank_statements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'account_number',
        'amount',
        'currency',
        'reference_no',
        'reference_date',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
