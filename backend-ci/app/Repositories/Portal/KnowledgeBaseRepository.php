<?php

namespace App\Repositories\Portal;

use App\Models\KnowledgeBaseCategoryModel;
use App\Models\KnowledgeBaseArticleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Knowledge base repository.
 *
 * @agent-repository: Knowledge base
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class KnowledgeBaseRepository
{
    protected KnowledgeBaseCategoryModel $categories;
    protected KnowledgeBaseArticleModel $articles;
    protected BaseConnection $db;

    public function __construct(
        ?KnowledgeBaseCategoryModel $categories = null,
        ?KnowledgeBaseArticleModel $articles = null,
        ?BaseConnection $db = null
    ) {
        $this->categories = $categories ?? new KnowledgeBaseCategoryModel();
        $this->articles = $articles ?? new KnowledgeBaseArticleModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function createCategory(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->categories->insert($payload);
        $payload['id'] = (int) $this->categories->getInsertID();
        return $payload;
    }

    public function createArticle(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->articles->insert($payload);
        $payload['id'] = (int) $this->articles->getInsertID();
        return $payload;
    }

    public function listCategories(): array
    {
        return $this->categories->orderBy('name', 'ASC')->findAll();
    }

    public function listArticles(array $filters = []): array
    {
        $b = $this->articles->builder();
        if (isset($filters['is_published'])) {
            $b->where('is_published', $filters['is_published']);
        }
        if (! empty($filters['category_id'])) {
            $b->where('category_id', $filters['category_id']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function findArticle(int $id): ?array
    {
        $row = $this->articles->find($id);
        return $row ?: null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
