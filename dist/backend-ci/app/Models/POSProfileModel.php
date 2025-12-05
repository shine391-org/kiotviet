<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS profile schema. @agent-model: pos_profiles */
class POSProfileModel extends Model
{
    protected $table = 'pos_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'name',
        'user_id',
        'role_id',
        'price_list_id',
        'tax_template_id',
        'warehouse_id',
        'branch_id',
        'company',
        'allow_offline',
        'require_shift',
        'credit_limit',
        'status',
        'created_at',
        'updated_at',
    ];
}
