<?php

namespace App\Models;

use CodeIgniter\Model;

class BankAccountModel extends Model
{
    protected $table = 'bank_accounts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'branch_name',
        'branch_id',
        'is_default',
        'is_active',
        'qr_template',
        'notes',
        'sort_order',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
