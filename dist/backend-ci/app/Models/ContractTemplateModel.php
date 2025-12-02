<?php

namespace App\Models;

use CodeIgniter\Model;

/** Contract template schema. @agent-model: contract_templates */
class ContractTemplateModel extends Model
{
    protected $table = 'contract_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'terms',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
