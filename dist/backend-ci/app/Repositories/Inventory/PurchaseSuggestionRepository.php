<?php

namespace App\Repositories\Inventory;

use App\Models\PurchaseSuggestionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Purchase suggestion persistence.
 *
 * @agent-repository: Purchase suggestions
 * @agent-pattern: Repository pattern + deduplication
 * @agent-reusable: MEDIUM
 */
class PurchaseSuggestionRepository
{
    protected PurchaseSuggestionModel $suggestions;
    protected BaseConnection $db;

    public function __construct(?PurchaseSuggestionModel $suggestions = null, ?BaseConnection $db = null)
    {
        $this->suggestions = $suggestions ?? new PurchaseSuggestionModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function find(int $id): ?array
    {
        $row = $this->suggestions->where('id', $id)->first();
        return $row ?: null;
    }

    /**
     * List purchase suggestions.
     * @agent-use: Suggestions listing
     */
    public function list(array $filters = []): array
    {
        $b = $this->db->table('purchase_suggestions');
        if (! empty($filters['branch_id'])) {
            $b->where('branch_id', (int) $filters['branch_id']);
        }
        if (! empty($filters['product_id'])) {
            $b->where('product_id', (int) $filters['product_id']);
        }
        if (array_key_exists('variant_id', $filters) && $filters['variant_id'] !== null && $filters['variant_id'] !== '') {
            $b->where('variant_id', (int) $filters['variant_id']);
        } elseif (array_key_exists('variant_id', $filters) && $filters['variant_id'] === null) {
            $b->where('variant_id', null);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['generated_for_date'])) {
            $b->where('generated_for_date', $filters['generated_for_date']);
        }
        return $b->orderBy('generated_for_date', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public function findExistingForDate(int $productId, ?int $variantId, int $branchId, string $date): ?array
    {
        $b = $this->suggestions->where('product_id', $productId)->where('branch_id', $branchId)->where('generated_for_date', $date);
        if ($variantId === null) {
            $b->where('variant_id', null);
        } else {
            $b->where('variant_id', $variantId);
        }
        $row = $b->first();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $payload = $data + [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->suggestions->insert($payload);
        $payload['id'] = (int) $this->suggestions->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => date('Y-m-d H:i:s')];
        $this->suggestions->update($id, $payload);
        return $this->find($id) ?? [];
    }

    /**
     * Create or update suggestion for same product/branch/day (dedup).
     * @agent-use: Generation deduplication
     */
    public function createOrUpdateForDate(array $data): array
    {
        $existing = $this->findExistingForDate(
            (int) $data['product_id'],
            $data['variant_id'] ?? null,
            (int) $data['branch_id'],
            $data['generated_for_date']
        );

        if (! $existing) {
            return $this->create($data);
        }

        if (($existing['status'] ?? '') === 'converted') {
            return $existing;
        }

        $update = $data;
        unset($update['generated_for_date'], $update['product_id'], $update['branch_id'], $update['variant_id']);
        unset($update['acknowledged_at'], $update['acknowledged_by'], $update['purchase_order_id'], $update['converted_at']);
        $update['status'] = $existing['status'] ?? ($data['status'] ?? 'pending');

        return $this->update((int) $existing['id'], $update);
    }
}
