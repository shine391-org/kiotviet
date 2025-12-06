<?php

namespace App\Services\DeliveryPartners;

use App\Repositories\DeliveryPartners\DeliveryPartnerRepository;
use App\Validators\DeliveryPartnerValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Delivery Partner business logic.
 * Uses partners table with type='delivery'
 *
 * @agent-service: DeliveryPartners
 * @agent-pattern: Service orchestrator
 */
class DeliveryPartnerService
{
    protected DeliveryPartnerRepository $repo;
    protected DeliveryPartnerValidator $validator;

    public function __construct(
        ?DeliveryPartnerRepository $repo = null,
        ?DeliveryPartnerValidator $validator = null
    ) {
        $this->repo = $repo ?? new DeliveryPartnerRepository();
        $this->validator = $validator ?? new DeliveryPartnerValidator();
    }

    /** List delivery partners with filters. */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        $summary = $this->repo->summary($validated);

        return [
            'success' => true,
            'data' => $rows,
            'pagination' => $this->pagination($validated, $total),
            'summary' => $summary,
        ];
    }

    /** Get single delivery partner. */
    public function get(int $id): array
    {
        $partner = $this->repo->findById($id);
        if (! $partner) {
            throw new RuntimeException('Delivery partner not found');
        }
        return ['success' => true, 'data' => $partner];
    }

    /** Create new delivery partner. */
    public function create(array $payload): array
    {
        $validated = $this->validator->validateCreate($payload);

        // Check code uniqueness
        if ($this->repo->codeExists($validated['code'])) {
            throw new InvalidArgumentException('Partner code already exists');
        }

        $partner = $this->repo->create($validated);

        return [
            'success' => true,
            'data' => $partner,
            'message' => 'Delivery partner created successfully',
        ];
    }

    /** Update delivery partner. */
    public function update(int $id, array $payload): array
    {
        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Delivery partner not found');
        }

        $validated = $this->validator->validateUpdate($payload);

        // Check code uniqueness if changed
        if (isset($validated['code']) && $validated['code'] !== $existing['code']) {
            if ($this->repo->codeExists($validated['code'], $id)) {
                throw new InvalidArgumentException('Partner code already exists');
            }
        }

        $partner = $this->repo->update($id, $validated);

        return [
            'success' => true,
            'data' => $partner,
            'message' => 'Delivery partner updated successfully',
        ];
    }

    /** Delete delivery partner. */
    public function delete(int $id): array
    {
        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Delivery partner not found');
        }

        $this->repo->delete($id);

        return [
            'success' => true,
            'message' => 'Delivery partner deleted successfully',
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
}
