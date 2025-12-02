<?php

namespace App\Models;

use CodeIgniter\Model;

/** Tax template item schema. @agent-model: tax_template_items */
class TaxTemplateItemModel extends Model
{
    protected $table = 'tax_template_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'template_id',
        'tax_name',
        'rate_percent',
        'charge_type',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
