<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Portal user schema.
 *
 * @agent-model: portal_users
 * @agent-pattern: CI4 model
 */
class PortalUserModel extends Model
{
    protected $table = 'portal_users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'email',
        'password_hash',
        'status',
        'last_login_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
