<?php

namespace App\Repositories\ProductVariants;

use App\Models\ProductVariantV2Model;
use CodeIgniter\Database\BaseConnection;

/** Product variant database operations. @agent-repository: Variant persistence @agent-pattern: Repository pattern @agent-reusable: HIGH */
class ProductVariantRepository
{
    protected ProductVariantV2Model $variants; protected BaseConnection $db;
    public function __construct(?ProductVariantV2Model $model = null, ?BaseConnection $db = null) { $this->variants = $model ?? new ProductVariantV2Model(); $this->db = $db ?? \Config\Database::connect(); }

    /** Check duplicate SKU inside variants. @agent-use: SKU uniqueness @agent-pattern: Exists check */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $builder = $this->variants->where('sku', $sku)->where('deleted_at', null);
        if ($excludeId) { $builder->where('id !=', $excludeId); }
        return $builder->countAllResults() > 0;
    }

    /** Cross-check variant SKU against product codes. @agent-use: Cross-table validation @agent-pattern: Prevent product/variant collision */
    public function skuExistsInProducts(string $sku): bool
    {
        return $this->db->table('db_products')->where('code', $sku)->where('deleted_at', null)->countAllResults() > 0;
    }

    /** Find variant by id (soft-deleted aware). @agent-use: Fetch variant @agent-pattern: Find by id */
    public function findById(int $id, bool $withDeleted = false): ?array
    {
        $variant = ($withDeleted ? $this->variants->withDeleted() : $this->variants)->find($id);
        if (!$variant) {
            return null;
        }
        return is_array($variant) ? $variant : (array) $variant;
    }

    /** Create new variant row. @agent-use: Create flow @agent-pattern: Insert with timestamps */
    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()]; $this->variants->insert($payload); $payload['id'] = $this->variants->getInsertID(); return $payload;
    }

    /** Update variant row. @agent-use: Update flow @agent-pattern: Update by id */
    public function update(int $id, array $data): bool { return (bool) $this->variants->update($id, $data + ['updated_at' => $this->now()]); }

    /** Soft delete variant. @agent-use: Delete flow @agent-pattern: Soft delete aware */
    public function delete(int $id): bool { return (bool) $this->variants->delete($id); }

    /** Hard delete variant. @agent-use: Hard delete flow @agent-pattern: Force delete */
    public function hardDelete(int $id): bool { return (bool) $this->variants->delete($id, true); }

    /** Restore deleted variant. @agent-use: Restore flow @agent-pattern: Soft delete aware */
    public function restore(int $id): bool { return (bool) $this->variants->update($id, ['deleted_at' => null, 'updated_at' => $this->now()]); }

    /** List soft-deleted variants. @agent-use: Admin listing @agent-pattern: Deleted scope */
    public function deletedList(?int $productId = null): array
    {
        $builder = $this->variants->onlyDeleted(); if ($productId) { $builder->where('product_id', $productId); }
        return $builder->findAll();
    }

    /**
     * Attach existing product images to variant with duplicate guard.
     *
     * @agent-use: Attach images to variant
     * @agent-pattern: Bulk update with skip list
     * @return array{attached_ids: int[], skipped_ids: int[], missing_ids: int[]}
     */
    public function attachImages(int $variantId, array $imageIds): array
    {
        if (empty($imageIds)) {
            return ['attached_ids' => [], 'skipped_ids' => [], 'missing_ids' => []];
        }

        $variant = $this->findById($variantId);
        if (! $variant) {
            return ['attached_ids' => [], 'skipped_ids' => [], 'missing_ids' => $imageIds];
        }

        $ids = array_values(array_unique(array_map('intval', $imageIds)));
        $rows = $this->db->table('db_product_images')
            ->select('id, variant_id, product_id, deleted_at')
            ->whereIn('id', $ids)
            ->get()
            ->getResultArray();

        $foundIds = array_map('intval', array_column($rows, 'id'));
        $missingIds = array_values(array_diff($ids, $foundIds));

        $alreadyAttached = [];
        $attachable = [];
        foreach ($rows as $row) {
            $rowId = (int) $row['id'];
            $currentVariant = isset($row['variant_id']) ? (int) $row['variant_id'] : null;
            $isActive = ($row['deleted_at'] ?? null) === null;

            if ($currentVariant === $variantId && $isActive) {
                $alreadyAttached[] = $rowId;
            } else {
                $attachable[] = $rowId;
            }
        }

        if ($attachable) {
            $this->db->table('db_product_images')
                ->whereIn('id', $attachable)
                ->update([
                    'variant_id' => $variantId,
                    'product_id' => $variant['product_id'] ?? null,
                    'deleted_at' => null,
                    'updated_at' => $this->now(),
                ]);
        }

        return [
            'attached_ids' => $attachable,
            'skipped_ids' => $alreadyAttached,
            'missing_ids' => $missingIds,
        ];
    }

    /** Get attribute values of variant with attribute meta. @agent-use: Attribute listing @agent-pattern: Join fetch */
    public function attributeValues(int $variantId): array
    {
        $query = $this->db->table('db_product_attribute_values pav')
            ->select('pav.*, pa.name AS attribute_name, pa.id as attribute_id, pa.type, pa.status, pa.sort_order, pa.code as slug, pa.is_required, pa.is_filterable, null as group_name, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at, null as attribute_parent_id, null as attribute_level, pa.code as attribute_code, null as attribute_description, null as attribute_unit, null as attribute_options, null as attribute_display_type, null as attribute_is_searchable, null as attribute_is_used_for_variations, null as attribute_is_highlight, null as attribute_meta, pa.status as attribute_status, null as attribute_is_system, null as attribute_is_default, null as attribute_position, null as attribute_created_by, null as attribute_updated_by, pa.is_filterable as attribute_filterable, null as attribute_comparable, null as attribute_visibility, null as attribute_required_at_checkout, null as attribute_default_value, null as attribute_help_text, null as attribute_icon, null as attribute_tooltip')
            ->join('db_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.variant_id', $variantId)
            ->where('pav.deleted_at', null);
            
        $result = $query->get();
        return $result ? $result->getResultArray() : [];
    }

    /** Sync attribute values for a variant. @agent-use: Sync attributes @agent-pattern: Delete + batch insert */
    public function syncAttributeValues(int $variantId, array $values): array
    {
        // First delete existing values for this variant
        $this->db->table('db_product_attribute_values')->where('variant_id', $variantId)->delete();
        
        if (empty($values)) {
            return [];
        }
        
        $rows = [];
        foreach ($values as $val) {
            if (empty($val['attribute_id'])) {
                continue;
            }
            $rows[] = [
                'product_id' => $val['product_id'] ?? null,
                'variant_id' => $variantId,
                'attribute_id' => $val['attribute_id'],
                'attribute_option_id' => $val['option_id'] ?? null,
                'value_text' => $val['value_text'] ?? null,
                'created_at' => $this->now(),
                'updated_at' => $this->now()
            ];
        }
        
        if (! empty($rows)) {
            $this->db->table('db_product_attribute_values')->insertBatch($rows);
        }
        
        return $rows;
    }

    /** Remove a single attribute from variant. @agent-use: Delete attribute @agent-pattern: Targeted delete */
    public function removeAttributeFromVariant(int $variantId, int $attributeId): int
    {
        return $this->db->table('db_product_attribute_values')->where('variant_id', $variantId)->where('attribute_id', $attributeId)->delete();
    }

    private function now(): string { return date('Y-m-d H:i:s'); }
}
