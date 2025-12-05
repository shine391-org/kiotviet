<?php

namespace App\Models;

use CodeIgniter\Model;

/** Chart of accounts schema. @agent-model: chart_of_accounts */
class ChartOfAccountModel extends Model
{
    protected $table = 'chart_of_accounts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'code',
        'name',
        'account_type',
        'currency',
        'parent_id',
        'is_group',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
