<?php

namespace App\Models;

use CodeIgniter\Model;

/** Contract fulfillment terms. @agent-model: contract_terms */
class ContractTermModel extends Model
{
    protected $table = 'contract_terms';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'contract_id',
        'description',
        'is_completed',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
