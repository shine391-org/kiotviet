<?php

namespace App\Services\ProductMedia;

use App\Repositories\ProductMedia\ProductMediaRepository;
use App\Transformers\ProductMediaTransformer;
use App\Validators\ProductMediaDateValidator;
use App\Validators\ProductMediaSearchValidator;
use App\Validators\ProductMediaValidator;

/**
 * Business logic for media library and search.
 *
 * @agent-service: Product media service layer
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ProductMediaService
{
    protected ProductMediaRepository $repo;
    protected ProductMediaValidator $validator;
    protected ProductMediaDateValidator $dateValidator;
    protected ProductMediaSearchValidator $searchValidator;
    protected ProductMediaTransformer $transformer;

    public function __construct(
        ?ProductMediaRepository $repo = null,
        ?ProductMediaValidator $validator = null,
        ?ProductMediaDateValidator $dateValidator = null,
        ?ProductMediaSearchValidator $searchValidator = null,
        ?ProductMediaTransformer $transformer = null
    ) {
        $this->repo = $repo ?? new ProductMediaRepository();
        $this->validator = $validator ?? new ProductMediaValidator();
        $this->dateValidator = $dateValidator ?? new ProductMediaDateValidator();
        $this->searchValidator = $searchValidator ?? new ProductMediaSearchValidator();
        $this->transformer = $transformer ?? new ProductMediaTransformer();
    }

    /**
     * List media library with attachment flags.
     *
     * @agent-use: GET /api/products/media/library
     * @agent-pattern: Standard list pattern
     */
    public function library(array $filters): array
    {
        $validated = $this->validator->validateLibraryFilters($filters);
        $result = $this->repo->listLibrary($validated);
        $data = $this->transformer->transformList($result['data'], $validated['entity_id']);

        return [
            'success' => true,
            'data' => $data,
            'pagination' => $this->formatPagination($validated, $result['total']),
        ];
    }

    /**
     * List media by date filters.
     *
     * @agent-use: GET /api/products/media/by-date
     * @agent-pattern: Simple filtered list
     */
    public function byDate(array $filters): array
    {
        $validated = $this->dateValidator->validateFilters($filters);
        $data = $this->repo->listByDate($validated);

        return [
            'success' => true,
            'data' => $this->transformer->transformList($data, $validated['entity_id']),
        ];
    }

    /**
     * Search media by SKU or product code.
     *
     * @agent-use: GET /api/products/media/search-sku
     * @agent-pattern: Search with minimum length guard
     */
    public function searchSku(array $filters): array
    {
        $sku = trim((string) ($filters['sku'] ?? ''));
        if (strlen($sku) < 2) {
            return ['success' => true, 'data' => [], 'total' => 0];
        }

        $validated = $this->searchValidator->validate($filters);
        $result = $this->repo->searchBySku($validated);

        return [
            'success' => true,
            'data' => $this->transformer->transformList($result['data'], $validated['entity_id']),
            'total' => $result['total'],
        ];
    }

    private function formatPagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $offset = $filters['offset'] ?? 0;
        $page = (int) floor($offset / ($limit ?: 1)) + 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }
}
