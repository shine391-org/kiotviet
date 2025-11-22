<?php

namespace App\Repositories\ProductVariants;

use App\Models\ProductVariantV2Model;
use CodeIgniter\Database\BaseConnection;

/** Product variant database operations. @agent-repository: Variant persistence @agent-pattern: Repository pattern @agent-reusable: HIGH */
class ProductVariantRepository
{
    protected ProductVariantV2Model $variants; protected BaseConnection $db;
    public function __construct(?ProductVariantV2Model $model = null, ?BaseConnection $db = null) { $this->variants = $model ?? new ProductVariantV2Model(); $this->db = $db ?? \Config\Database::connect(); }

    /** Find variant by id (soft-deleted aware). @agent-use: Fetch variant @agent-pattern: Find by id */
    public function findById(int $id, bool $withDeleted = false): ?array
    {
        $row = ($withDeleted ? $this->variants->withDeleted() : $this->variants)->find($id);
        return $row ?: null;
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

    /** Attach existing product images to variant. @agent-use: Attach images @agent-pattern: Bulk update */
    public function attachImages(int $variantId, array $imageIds): int
    {
        if (empty($imageIds)) { return 0; }
        $this->db->table('product_images')->whereIn('id', $imageIds)->update(['variant_id' => $variantId]);
        return count($imageIds);
    }

    /** Get attribute values of variant with attribute meta. @agent-use: Attribute listing @agent-pattern: Join fetch */
    public function attributeValues(int $variantId): array
    {
        return $this->db->table('product_attribute_values pav')->select('pav.*, pa.name AS attribute_name, pa.id as attribute_id, pa.type, pa.status, pa.sort_order, pa.slug, pa.is_required, pa.is_filterable, pa.group_name, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at, pa.parent_id as attribute_parent_id, pa.level as attribute_level, pa.code as attribute_code, pa.description as attribute_description, pa.unit as attribute_unit, pa.options as attribute_options, pa.display_type as attribute_display_type, pa.is_searchable as attribute_is_searchable, pa.is_used_for_variations as attribute_is_used_for_variations, pa.is_highlight as attribute_is_highlight, pa.meta as attribute_meta, pa.status as attribute_status, pa.is_system as attribute_is_system, pa.is_default as attribute_is_default, pa.position as attribute_position, pa.created_by as attribute_created_by, pa.updated_by as attribute_updated_by, pa.filterable as attribute_filterable, pa.comparable as attribute_comparable, pa.visibility as attribute_visibility, pa.required_at_checkout as attribute_required_at_checkout, pa.default_value as attribute_default_value, pa.help_text as attribute_help_text, pa.icon as attribute_icon, pa.tooltip as attribute_tooltip')->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')->where('pav.variant_id', $variantId)->where('pav.deleted_at', null)->get()->getResultArray();
    }

    /** Sync attribute values for a variant. @agent-use: Sync attributes @agent-pattern: Delete + batch insert */
    public function syncAttributeValues(int $variantId, array $values): array
    {
        $this->db->table('product_attribute_values')->where('variant_id', $variantId)->delete(); if (empty($values)) { return []; }
        $rows = [];
        foreach ($values as $val) { if (empty($val['attribute_id'])) { continue; } $rows[] = ['product_id' => $val['product_id'] ?? null, 'variant_id' => $variantId, 'attribute_id' => $val['attribute_id'], 'option_id' => $val['option_id'] ?? null, 'value_text' => $val['value_text'] ?? null, 'created_at' => $this->now(), 'updated_at' => $this->now()]; }
        if (! empty($rows)) { $this->db->table('product_attribute_values')->insertBatch($rows); }
        return $rows;
    }

    /** Remove a single attribute from variant. @agent-use: Delete attribute @agent-pattern: Targeted delete */
    public function removeAttributeFromVariant(int $variantId, int $attributeId): int
    {
        return $this->db->table('product_attribute_values')->where('variant_id', $variantId)->where('attribute_id', $attributeId)->delete();
    }

    private function now(): string { return date('Y-m-d H:i:s'); }
}
