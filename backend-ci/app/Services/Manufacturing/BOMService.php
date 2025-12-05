<?php

namespace App\Services\Manufacturing;

use App\Repositories\Manufacturing\BOMRepository;
use App\Validators\BOMValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * BOM business logic.
 *
 * @agent-service: BOM
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class BOMService
{
    protected BOMRepository $repo;
    protected BOMValidator $validator;

    public function __construct(?BOMRepository $repo = null, ?BOMValidator $validator = null)
    {
        $this->repo = $repo ?? new BOMRepository();
        $this->validator = $validator ?? new BOMValidator();
    }

    /** List BOMs. */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateFilters($filters);
        return ['success' => true, 'data' => $this->repo->list($validated), 'total' => $this->repo->count($validated)];
    }

    /** Show BOM. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireBOM($id)];
    }

    /** Create BOM with items. */
    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        if ($this->repo->existsActiveForProduct((int) $validated['product_id']) && ($validated['is_active'] ?? true)) {
            throw new InvalidArgumentException('Active BOM already exists for product');
        }
        $bom = [
            'product_id' => (int) $validated['product_id'],
            'version' => $validated['version'] ?? null,
            'quantity' => $validated['quantity'],
            'uom' => $validated['uom'] ?? null,
            'cost' => $validated['cost'],
            'is_active' => $validated['is_active'] ? 1 : 0,
        ];
        $created = $this->repo->create($bom, $validated['items']);
        return ['success' => true, 'data' => $created];
    }

    /** Update BOM metadata/items. */
    public function update(int $id, array $data): array
    {
        $existing = $this->requireBOM($id);
        $validated = $this->validator->validateUpdate($data);
        if (isset($validated['is_active']) && $validated['is_active'] && $existing['is_active'] == 0) {
            if ($this->repo->existsActiveForProduct((int) $existing['product_id'])) {
                throw new InvalidArgumentException('Another active BOM exists for product');
            }
        }

        $payload = $validated;
        $items = null;
        if (isset($validated['items'])) {
            $items = $validated['items'];
            unset($payload['items']);
        }

        $updated = $this->repo->update($id, $payload, $items);
        return ['success' => true, 'data' => $updated + ['items' => $items ?? $existing['items']]];
    }

    /** Delete BOM. */
    public function delete(int $id): array
    {
        $this->requireBOM($id);
        $this->repo->update($id, ['is_active' => 0]);
        return ['success' => true];
    }

    private function requireBOM(int $id): array
    {
        $bom = $this->repo->findWithItems($id);
        if (! $bom) {
            throw new RuntimeException('BOM not found');
        }
        return $bom;
    }
}
