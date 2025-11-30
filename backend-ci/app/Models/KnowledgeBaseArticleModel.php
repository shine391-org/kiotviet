<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Knowledge base article schema.
 *
 * @agent-model: knowledge_base_articles
 * @agent-pattern: CI4 model
 */
class KnowledgeBaseArticleModel extends Model
{
    protected $table = 'knowledge_base_articles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'category_id',
        'title',
        'content',
        'is_published',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
