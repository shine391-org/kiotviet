<?php

namespace App\Models;

use CodeIgniter\Model;

/** Tax template schema. @agent-model: tax_templates */
class TaxTemplateModel extends Model
{
    protected $table = 'tax_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'rate_percent',
        'is_inclusive',
        'rounding_rule',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
