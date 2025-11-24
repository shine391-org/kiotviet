<?php

namespace App\Services\PaymentMethods;

use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Transformers\PaymentMethodTransformer;
use App\Validators\PaymentMethodValidator;
use CodeIgniter\Cache\CacheInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Business logic for payment methods.
 *
 * @agent-service: Payment methods
 * @agent-pattern: Service orchestrator
 * @agent-reusable: HIGH
 */
class PaymentMethodService
{
    private const CACHE_ACTIVE_KEY = 'payment_methods_active_v1';

    protected PaymentMethodRepository $repo;
    protected PaymentMethodValidator $validator;
    protected PaymentMethodTransformer $transformer;
    protected CacheInterface $cache;

    public function __construct(
        ?PaymentMethodRepository $repo = null,
        ?PaymentMethodValidator $validator = null,
        ?PaymentMethodTransformer $transformer = null,
        ?CacheInterface $cache = null
    ) {
        $this->repo = $repo ?? new PaymentMethodRepository();
        $this->validator = $validator ?? new PaymentMethodValidator();
        $this->transformer = $transformer ?? new PaymentMethodTransformer();
        $this->cache = $cache ?? cache();
    }

    /** List methods (active by default). @agent-use: GET /api/payment-methods */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $useCache = $this->shouldUseCache($validated);

        if ($useCache) {
            $cached = $this->cache->get(self::CACHE_ACTIVE_KEY);
            if (is_array($cached)) {
                $sliced = $this->slice($cached, $validated);
                return [
                    'success' => true,
                    'data' => $this->transformer->transformList($sliced['data']),
                    'pagination' => $sliced['pagination'],
                ];
            }
        }

        if ($useCache) {
            $all = $this->repo->activeOrdered();
            $this->cache->save(self::CACHE_ACTIVE_KEY, $all, 3600);
            $sliced = $this->slice($all, $validated);
            return [
                'success' => true,
                'data' => $this->transformer->transformList($sliced['data']),
                'pagination' => $sliced['pagination'],
            ];
        }

        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);

        return [
            'success' => true,
            'data' => $this->transformer->transformList($rows),
            'pagination' => $this->pagination($validated, $total),
        ];
    }

    /** Get detail. @agent-use: GET /api/payment-methods/{id} */
    public function get(int $id): array
    {
        $method = $this->repo->findById($id);
        if (! $method) {
            throw new RuntimeException('Payment method not found');
        }
        return ['success' => true, 'data' => $this->transformer->transform($method)];
    }

    /** Create method. @agent-use: POST /api/payment-methods */
    public function create(array $payload): array
    {
        $validated = $this->validator->validateCreate($payload);
        if ($this->repo->codeExists($validated['code'])) {
            throw new InvalidArgumentException('Payment method code already exists');
        }
        $created = $this->repo->create($validated);
        $this->clearCache();
        return ['success' => true, 'data' => $this->transformer->transform($created)];
    }

    /** Update method. @agent-use: PUT /api/payment-methods/{id} */
    public function update(int $id, array $payload): array
    {
        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Payment method not found');
        }

        $validated = $this->validator->validateUpdate($payload);
        if (isset($validated['code']) && $this->repo->codeExists($validated['code'], $id)) {
            throw new InvalidArgumentException('Payment method code already exists');
        }

        $this->repo->update($id, $validated);
        $updated = $this->repo->findById($id) ?? $existing;
        $this->clearCache();

        return ['success' => true, 'data' => $this->transformer->transform($updated)];
    }

    /** Delete method (soft). Prevent delete if used. @agent-use: DELETE /api/payment-methods/{id} */
    public function delete(int $id): array
    {
        $method = $this->repo->findById($id);
        if (! $method) {
            throw new RuntimeException('Payment method not found');
        }
        if ($this->repo->usedInOrders($method['code'])) {
            throw new InvalidArgumentException('Cannot delete payment method that is used in orders');
        }

        $this->repo->delete($id);
        $this->clearCache();
        return ['success' => true];
    }

    /** Activate method. @agent-use: PATCH /api/payment-methods/{id}/activate */
    public function activate(int $id): array
    {
        return $this->toggleActive($id, true);
    }

    /** Deactivate method. @agent-use: PATCH /api/payment-methods/{id}/deactivate */
    public function deactivate(int $id): array
    {
        return $this->toggleActive($id, false);
    }

    private function toggleActive(int $id, bool $active): array
    {
        $method = $this->repo->findById($id);
        if (! $method) {
            throw new RuntimeException('Payment method not found');
        }
        if ($method['is_active'] === $active) {
            return ['success' => true, 'data' => $this->transformer->transform($method)];
        }
        $this->repo->update($id, ['is_active' => $active]);
        $updated = $this->repo->findById($id) ?? $method;
        $this->clearCache();
        return ['success' => true, 'data' => $this->transformer->transform($updated)];
    }

    private function shouldUseCache(array $filters): bool
    {
        return ($filters['is_active'] ?? false) === true
            && empty($filters['search'])
            && (($filters['page'] ?? 1) >= 1);
    }

    private function slice(array $rows, array $filters): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $data = array_slice($rows, $offset, $limit);

        return [
            'data' => $data,
            'pagination' => $this->pagination($filters, count($rows)),
        ];
    }

    private function pagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    private function clearCache(): void
    {
        $this->cache->delete(self::CACHE_ACTIVE_KEY);
    }
}
