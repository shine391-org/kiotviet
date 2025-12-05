<?php

namespace App\Services\Portal;

use App\Repositories\Portal\KnowledgeBaseRepository;
use RuntimeException;

/**
 * Knowledge base service.
 *
 * @agent-service: Knowledge base
 * @agent-pattern: Read-only listing
 * @agent-reusable: MEDIUM
 */
class KnowledgeBaseService
{
    protected KnowledgeBaseRepository $repo;

    public function __construct(?KnowledgeBaseRepository $repo = null)
    {
        $this->repo = $repo ?? new KnowledgeBaseRepository();
    }

    /** @agent-use: GET /api/knowledge-base/categories */
    public function categories(): array
    {
        return ['success' => true, 'data' => $this->repo->listCategories()];
    }

    /** @agent-use: GET /api/knowledge-base/articles */
    public function articles(array $filters): array
    {
        $filters['is_published'] = $filters['is_published'] ?? 1;
        return ['success' => true, 'data' => $this->repo->listArticles($filters)];
    }

    /** @agent-use: GET /api/knowledge-base/articles/{id} */
    public function show(int $id): array
    {
        $article = $this->repo->findArticle($id);
        if (! $article || empty($article['is_published'])) {
            throw new RuntimeException('Article not found');
        }
        return ['success' => true, 'data' => $article];
    }
}
