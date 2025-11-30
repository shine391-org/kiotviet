<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Knowledge base category schema.
 *
 * @agent-model: knowledge_base_categories
 * @agent-pattern: CI4 model
 */
class KnowledgeBaseCategoryModel extends Model
{
    protected $table = 'knowledge_base_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
