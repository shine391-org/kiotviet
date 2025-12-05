<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Company permission mapping model.
 *
 * @agent-model: company_permissions
 * @agent-pattern: CI4 model
 */
class CompanyPermissionModel extends Model
{
    protected $table = 'company_permissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id',
        'user_id',
        'role_name',
        'permissions',
        'created_at',
        'updated_at',
    ];
}
