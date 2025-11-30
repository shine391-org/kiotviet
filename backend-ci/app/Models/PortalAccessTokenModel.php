<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Portal access token schema.
 *
 * @agent-model: portal_access_tokens
 * @agent-pattern: CI4 model
 */
class PortalAccessTokenModel extends Model
{
    protected $table = 'portal_access_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'portal_user_id',
        'token',
        'expires_at',
        'created_at',
    ];
    protected $useSoftDeletes = false;
}
