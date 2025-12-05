<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\PurchaseSuggestionRepository;
use App\Repositories\Inventory\ReorderLevelRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Validators\PurchaseSuggestionValidator;
use App\Validators\ReorderLevelValidator;
use InvalidArgumentException;

/**
 * Reorder planning logic based on stock bins.
 *
 * @agent-service: Reorder planning
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ReorderPlanningService
{
    protected ReorderLevelRepository $levels;
    protected PurchaseSuggestionRepository $suggestions;
    protected StockBinRepository $bins;
    protected ReorderLevelValidator $levelValidator;
    protected PurchaseSuggestionValidator $suggestionValidator;

    public function __construct(
        ?ReorderLevelRepository $levels = null,
        ?PurchaseSuggestionRepository $suggestions = null,
        ?StockBinRepository $bins = null,
        ?ReorderLevelValidator $levelValidator = null,
        ?PurchaseSuggestionValidator $suggestionValidator = null
    ) {
        $db = $bins ? $bins->db() : null;
        $this->bins = $bins ?? new StockBinRepository(null, $db);
        $this->levels = $levels ?? new ReorderLevelRepository(null, $db);
        $this->suggestions = $suggestions ?? new PurchaseSuggestionRepository(null, $db);
        $this->levelValidator = $levelValidator ?? new ReorderLevelValidator();
        $this->suggestionValidator = $suggestionValidator ?? new PurchaseSuggestionValidator();
    }

    /**
     * List reorder levels with current stock snapshot.
     * @agent-use: GET /api/reorder-levels
     * @agent-pattern: Delegate listing
     */
    public function listLevels(array $filters): array
    {
        $validated = $this->levelValidator->validateFilters($filters);
        return ['success' => true, 'data' => $this->levels->list($validated)];
    }

    /**
     * Create reorder level.
     * @agent-use: POST /api/reorder-levels
     * @agent-pattern: Validation + duplicate guard
     */
    public function createLevel(array $data): array
    {
        $validated = $this->levelValidator->validateCreate($data);
        $existing = $this->levels->findByKey($validated['product_id'], $validated['variant_id'] ?? null, $validated['branch_id']);
        if ($existing) {
            throw new InvalidArgumentException('Reorder level already exists for this branch/product');
        }
        $level = $this->levels->create($validated);
        return ['success' => true, 'data' => $level];
    }

    /**
     * Update reorder level.
     * @agent-use: PUT /api/reorder-levels/{id}
     * @agent-pattern: Validate + conflict check
     */
    public function updateLevel(int $id, array $data): array
    {
        $current = $this->requireLevel($id);
        $validated = $this->levelValidator->validateUpdate($data);

        $productId = array_key_exists('product_id', $validated) ? (int) $validated['product_id'] : (int) $current['product_id'];
        $variantId = array_key_exists('variant_id', $validated) ? $validated['variant_id'] : $current['variant_id'];
        $branchId = array_key_exists('branch_id', $validated) ? (int) $validated['branch_id'] : (int) $current['branch_id'];

        $existing = $this->levels->findByKey($productId, $variantId, $branchId);
        if ($existing && (int) $existing['id'] !== $id) {
            throw new InvalidArgumentException('Another reorder level already exists for this branch/product');
        }

        $update = $validated + ['product_id' => $productId, 'branch_id' => $branchId, 'variant_id' => $variantId];
        $level = $this->levels->update($id, $update);
        return ['success' => true, 'data' => $level];
    }

    /**
     * Delete reorder level (soft).
     * @agent-use: DELETE /api/reorder-levels/{id}
     * @agent-pattern: Soft delete
     */
    public function deleteLevel(int $id): array
    {
        $this->requireLevel($id);
        $this->levels->delete($id);
        return ['success' => true];
    }

    /**
     * Generate purchase suggestions from stock bins.
     * @agent-use: POST /api/purchase-suggestions/generate
     * @agent-pattern: Threshold comparison + dedup
     */
    public function generateSuggestions(array $filters): array
    {
        $validated = $this->suggestionValidator->validateGenerate($filters);
        $date = $validated['generated_for_date'] ?? date('Y-m-d');

        $shortages = $this->levels->findShortages($validated);
        $suggestions = [];
        foreach ($shortages as $level) {
            $available = (float) ($level['available_qty'] ?? 0);
            $threshold = (float) ($level['min_level'] ?? 0) + (float) ($level['safety_stock'] ?? 0);
            if ($available > $threshold) {
                continue;
            }
            $target = (float) ($level['max_level'] ?? 0);
            if ($target <= 0) {
                $target = $threshold;
            }
            $suggestedQty = max($target - $available, 0);
            if ($suggestedQty <= 0) {
                continue;
            }

            $payload = [
                'reorder_level_id' => $level['id'],
                'product_id' => (int) $level['product_id'],
                'variant_id' => $level['variant_id'] === null ? null : (int) $level['variant_id'],
                'branch_id' => (int) $level['branch_id'],
                'generated_for_date' => $date,
                'suggested_qty' => $suggestedQty,
                'on_hand_qty' => (float) ($level['on_hand_qty'] ?? 0),
                'reserved_qty' => (float) ($level['reserved_qty'] ?? 0),
                'available_qty' => $available,
                'min_level' => (float) ($level['min_level'] ?? 0),
                'max_level' => (float) ($level['max_level'] ?? 0),
                'safety_stock' => (float) ($level['safety_stock'] ?? 0),
                'status' => 'pending',
                'reason' => sprintf('Available %.3f below threshold %.3f', $available, $threshold),
            ];
            $suggestions[] = $this->suggestions->createOrUpdateForDate($payload);
        }

        return [
            'success' => true,
            'data' => $suggestions,
            'generated_for_date' => $date,
        ];
    }

    private function requireLevel(int $id): array
    {
        $level = $this->findWithActive($id);
        if (! $level) {
            throw new InvalidArgumentException('Reorder level not found');
        }
        return $level;
    }

    private function findWithActive(int $id): ?array
    {
        $level = $this->levels->find($id);
        return $level && ($level['deleted_at'] ?? null) === null ? $level : null;
    }
}
