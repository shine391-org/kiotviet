<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Document share mapping model.
 *
 * @agent-model: document_shares
 * @agent-pattern: CI4 model
 */
class DocumentShareModel extends Model
{
    protected $table = 'document_shares';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id',
        'entity_type',
        'entity_id',
        'shared_with_user_id',
        'shared_with_role',
        'permissions',
        'expires_at',
        'created_by',
        'created_at',
        'updated_at',
    ];
}
