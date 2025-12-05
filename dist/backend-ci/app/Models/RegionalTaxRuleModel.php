<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Regional tax rule schema.
 *
 * @agent-model: regional_tax_rules
 * @agent-pattern: CI4 model
 */
class RegionalTaxRuleModel extends Model
{
    protected $table = 'regional_tax_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'country',
        'rule_json',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
