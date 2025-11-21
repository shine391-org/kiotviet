<?php

namespace App\Repositories\Products;

use App\Models\ProductCategoryLinkModel;
use App\Models\ProductModel;
use App\Models\ProductVariantV2Model;
use CodeIgniter\Database\BaseConnection;

/** Product DB operations. @agent-repository: Product persistence @agent-pattern: Repository pattern @agent-reusable: HIGH */
class ProductRepository
{
    protected ProductModel $products; protected ProductCategoryLinkModel $links; protected ProductVariantV2Model $variants; protected BaseConnection $db;
    public function __construct() { $this->products = new ProductModel(); $this->links = new ProductCategoryLinkModel(); $this->variants = new ProductVariantV2Model(); $this->db = \Config\Database::connect(); }

    /** List products with filters + pagination. @agent-use: Product listing @agent-pattern: Standard query pattern */
    public function findAll(array $filters): array { $b = $this->applyFilters($filters); $limit = $filters['limit'] ?? 20; $offset = (($filters['page'] ?? 1) - 1) * $limit; return $b->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray(); }

    /** Count products for pagination. @agent-use: Product listing @agent-pattern: Count with filters */
    public function count(array $filters): int { return $this->applyFilters($filters)->countAllResults(); }

    /** Find product by id. @agent-use: Fetch single product @agent-pattern: Find by id */
    public function findById(int $id): ?array { $row = $this->products->where('deleted_at', null)->find($id); return $row ?: null; }

    /** Create product row. @agent-use: Create flow @agent-pattern: Insert with timestamps */
    public function create(array $data): array { $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()]; $this->products->insert($payload); $payload['id'] = $this->products->getInsertID(); return $payload; }

    /** Update product row. @agent-use: Update flow @agent-pattern: Update by id */
    public function update(int $id, array $data): bool { $payload = $data + ['updated_at' => $this->now()]; return (bool) $this->products->update($id, $payload); }

    /** Soft delete. @agent-use: Delete flow @agent-pattern: Soft delete aware */
    public function delete(int $id): bool { return (bool) $this->products->delete($id); }

    /** Check duplicate code. @agent-use: Code uniqueness @agent-pattern: Exists check */
    public function codeExists(string $code, ?int $excludeId = null): bool { $b = $this->products->where('code', $code)->where('deleted_at', null); if ($excludeId) { $b->where('id !=', $excludeId); } return $b->countAllResults() > 0; }

    /** Map product => category ids. @agent-use: Attach categories @agent-pattern: Batch fetch */
    public function categoryMap(array $productIds): array { if (empty($productIds)) { return []; } $rows = $this->links->select('product_id, category_id')->whereIn('product_id', $productIds)->findAll(); $map = []; foreach ($rows as $row) { $map[$row['product_id']][] = (int) $row['category_id']; } return $map; }

    /** Variants grouped by product. @agent-use: Include variants @agent-pattern: Batch fetch */
    public function variantMap(array $productIds): array { if (empty($productIds)) { return []; } $rows = $this->variants->where('deleted_at', null)->whereIn('product_id', $productIds)->findAll(); $map = []; foreach ($rows as $row) { $map[$row['product_id']][] = $row; } return $map; }

    /** Variants for one product. @agent-use: Variant listing @agent-pattern: Simple find */
    public function variantsByProduct(int $productId): array { return $this->variants->where('deleted_at', null)->where('product_id', $productId)->findAll(); }

    /** List product images. @agent-use: Media listing @agent-pattern: Soft delete aware */
    public function images(int $productId): array { return $this->db->table('product_images')->where('product_id', $productId)->where('deleted_at', null)->orderBy('is_primary', 'DESC')->orderBy('sort_order', 'ASC')->get()->getResultArray(); }

    /** Insert image rows. @agent-use: Upload flows @agent-pattern: Batch insert */
    public function insertImages(array $rows): array
    {
        if (empty($rows)) { return []; }
        if (count($rows) === 1) { $this->db->table('product_images')->insert($rows[0]); $rows[0]['id'] = $this->db->insertID(); return $rows; }
        $this->db->table('product_images')->insertBatch($rows); return $rows;
    }

    /** Attach existing images to product. @agent-use: Attach images @agent-pattern: Bulk update */
    public function attachImages(int $productId, array $imageIds): int { if (empty($imageIds)) { return 0; } $this->db->table('product_images')->whereIn('id', $imageIds)->update(['product_id' => $productId, 'deleted_at' => null]); return count($imageIds); }

    /** Set primary image. @agent-use: Primary image @agent-pattern: Two-step update */
    public function setPrimaryImage(int $imageId): ?array { $image = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray(); if (! $image) { return null; } $this->db->table('product_images')->where('product_id', $image['product_id'])->update(['is_primary' => 0]); $this->db->table('product_images')->where('id', $imageId)->update(['is_primary' => 1]); return $image; }

    /** Delete image (soft/hard). @agent-use: Remove image @agent-pattern: Soft delete aware */
    public function deleteImage(int $imageId, bool $hard = false): void { $table = $this->db->table('product_images'); $hard ? $table->delete(['id' => $imageId]) : $table->where('id', $imageId)->update(['deleted_at' => $this->now()]); }

    /** Used attribute options for product. @agent-use: Attribute overview @agent-pattern: Join fetch */
    public function usedAttributeOptions(int $productId): array { return $this->db->table('product_attribute_values pav')->select('pav.attribute_id, pav.option_id, pa.name as attribute_name, pa.type, pa.attribute_key, pa.slug, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, pa.is_visible, pa.created_at, pa.updated_at')->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')->where('pav.product_id', $productId)->where('pav.deleted_at', null)->get()->getResultArray(); }

    /** Attribute values for product. @agent-use: Attribute listing @agent-pattern: Join fetch */
    public function productAttributeValues(int $productId): array { return $this->db->table('product_attribute_values pav')->select('pav.*, pa.name as attribute_name, pa.type, pa.attribute_key, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, pa.is_visible, pa.slug, pa.attribute_values, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at')->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')->where('pav.product_id', $productId)->where('pav.deleted_at', null)->get()->getResultArray(); }

    /** Sync product-level attribute values. @agent-use: Update attributes @agent-pattern: Delete + batch insert */
    public function syncProductAttributeValues(int $productId, array $values): array { $this->db->table('product_attribute_values')->where('product_id', $productId)->where('variant_id', null)->delete(); if (empty($values)) { return []; } $rows = []; foreach ($values as $val) { if (empty($val['attribute_id'])) { continue; } $rows[] = ['product_id' => $productId, 'variant_id' => $val['variant_id'] ?? null, 'attribute_id' => $val['attribute_id'], 'option_id' => $val['option_id'] ?? null, 'value_text' => $val['value_text'] ?? null, 'created_at' => $this->now(), 'updated_at' => $this->now()]; } $this->db->table('product_attribute_values')->insertBatch($rows); return $rows; }

    /** Remove single attribute from product. @agent-use: Delete attribute @agent-pattern: Targeted delete */
    public function removeAttributeFromProduct(int $productId, int $attributeId): int { return $this->db->table('product_attribute_values')->where('product_id', $productId)->where('attribute_id', $attributeId)->delete(); }

    /** Total stock for analytics. @agent-use: Analytics @agent-pattern: Aggregate */
    public function totalStock(int $productId): float { $row = $this->db->table('product_variants_v2')->selectSum('stock_quantity')->where('product_id', $productId)->get()->getRowArray(); return (float) ($row['stock_quantity'] ?? 0); }

    private function applyFilters(array $filters) { $b = $this->products->builder()->where('deleted_at', null); if (! empty($filters['search'])) { $b->groupStart()->like('code', $filters['search'])->orLike('name', $filters['search'])->orLike('barcode', $filters['search'])->groupEnd(); } if (! empty($filters['status'])) { $b->where('status', $filters['status']); } if (! empty($filters['product_type'])) { $b->where('product_type', $filters['product_type']); } return $b; }

    private function now(): string { return date('Y-m-d H:i:s'); }
}
