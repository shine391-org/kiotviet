<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Company entity model.
 *
 * @agent-model: companies
 * @agent-pattern: CI4 model
 */
class CompanyModel extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['code', 'name', 'is_default', 'status', 'created_at', 'updated_at'];
}
