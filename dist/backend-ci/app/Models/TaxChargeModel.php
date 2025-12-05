<?php

namespace App\Models;

use CodeIgniter\Model;

/** Tax charge lines. @agent-model: tax_charges */
class TaxChargeModel extends Model
{
    protected $table = 'tax_charges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'template_id',
        'name',
        'rate_percent',
        'charge_type',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
