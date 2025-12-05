<?php

namespace App\Services\Customers;

use App\Repositories\Customers\CustomerRepository;
use App\Transformers\CustomerTransformer;
use App\Validators\CustomerValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Business logic for customers.
 *
 * @agent-service: Customers
 * @agent-pattern: Service orchestrator
 * @agent-reusable: HIGH
 */
class CustomerService
{
    protected CustomerRepository $repo;
    protected CustomerValidator $validator;
    protected CustomerTransformer $transformer;

    public function __construct(
        ?CustomerRepository $repo = null,
        ?CustomerValidator $validator = null,
        ?CustomerTransformer $transformer = null
    ) {
        $this->repo = $repo ?? new CustomerRepository();
        $this->validator = $validator ?? new CustomerValidator();
        $this->transformer = $transformer ?? new CustomerTransformer();
    }

    /** List customers with pagination. @agent-use: GET /api/customers */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);

        return [
            'success' => true,
            'data' => $this->transformer->transformList($rows),
            'pagination' => $this->pagination($validated, $total),
        ];
    }

    /** Get customer detail. @agent-use: GET /api/customers/{id} */
    public function get(int $id): array
    {
        $customer = $this->repo->findById($id);
        if (! $customer) {
            throw new RuntimeException('Customer not found');
        }

        return ['success' => true, 'data' => $this->transformer->transform($customer)];
    }

    /** Create customer. @agent-use: POST /api/customers */
    public function create(array $payload): array
    {
        $validated = $this->validator->validateCreate($payload);
        $orgId = $validated['organization_id'] ?? 1;

        $this->guardUniqueTaxCode($validated['tax_code'] ?? null, null, $orgId);

        $created = $this->repo->create($validated);

        return [
            'success' => true,
            'data' => $this->transformer->transform($created),
        ];
    }

    /** Update customer. @agent-use: PUT /api/customers/{id} */
    public function update(int $id, array $payload): array
    {
        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Customer not found');
        }

        $validated = $this->validator->validateUpdate($payload);
        $orgId = $validated['organization_id'] ?? ($existing['organization_id'] ?? 1);

        if (array_key_exists('tax_code', $validated)) {
            $this->guardUniqueTaxCode($validated['tax_code'], $id, $orgId);
        }

        $this->repo->update($id, $validated);
        $updated = $this->repo->findById($id) ?? $existing;

        return [
            'success' => true,
            'data' => $this->transformer->transform($updated),
        ];
    }

    private function guardUniqueTaxCode(?string $taxCode, ?int $excludeId, int $organizationId): void
    {
        if ($taxCode === null || $taxCode === '') {
            return;
        }

        if ($this->repo->taxCodeExists($taxCode, $excludeId, $organizationId)) {
            throw new InvalidArgumentException('Tax code already exists for this organization');
        }
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

    /**
     * Export customers as array (CSV handled in controller).
     *
     * @agent-use: GET /api/customers/export
     */
    public function export(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        return $this->transformer->transformList($rows);
    }

    /**
     * Import customers from parsed rows.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array{imported:int,errors:int}
     *
     * @agent-use: POST /api/customers/import
     */
    public function import(array $rows): array
    {
        $imported = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $this->create($row);
                $imported++;
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
