<?php

namespace App\Models;

use CodeIgniter\Model;

/** Contract schema. @agent-model: contracts */
class ContractModel extends Model
{
    protected $table = 'contracts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'template_id',
        'start_date',
        'end_date',
        'value',
        'status',
        'auto_renew',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
