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

    public function __construct(?ProductModel $products = null, ?ProductCategoryLinkModel $links = null, ?ProductVariantV2Model $variants = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->products = $products ?? new ProductModel($this->db);
        $this->links = $links ?? new ProductCategoryLinkModel($this->db);
        $this->variants = $variants ?? new ProductVariantV2Model($this->db);
    }

    /** List products with filters + pagination. @agent-use: Product listing @agent-pattern: Standard query pattern */
    public function findAll(array $filters): array { $b = $this->applyFilters($filters); $limit = $filters['limit'] ?? 20; $offset = (($filters['page'] ?? 1) - 1) * $limit; return $b->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray(); }

    /** Count products for pagination. @agent-use: Product listing @agent-pattern: Count with filters */
    public function count(array $filters): int { return $this->applyFilters($filters)->countAllResults(); }

    /** Find product by id. @agent-use: Fetch single product @agent-pattern: Find by id */
    public function findById(int $id): ?array {
        $product = $this->products->where('deleted_at', null)->find($id);
        if (!$product) {
            return null;
        }
        return is_array($product) ? $product : (array) $product;
    }

    /** Find product by code. @agent-use: Import/update @agent-pattern: Find by unique code */
    public function findByCode(string $code): ?array
    {
        $row = $this->products->where('deleted_at', null)->where('code', $code)->first();
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    /** Create product row. @agent-use: Create flow @agent-pattern: Insert with timestamps */
    public function create(array $data): array { $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()]; $this->products->insert($payload); $payload['id'] = $this->products->getInsertID(); return $payload; }

    /** Update product row. @agent-use: Update flow @agent-pattern: Update by id */
    public function update(int $id, array $data): bool { $payload = $data + ['updated_at' => $this->now()]; return (bool) $this->products->update($id, $payload); }

    /** Soft delete. @agent-use: Delete flow @agent-pattern: Soft delete aware */
    public function delete(int $id): bool {
        if ($id <= 0) {
            return false;
        }
        return (bool) $this->products->delete($id);
    }

    /** Check duplicate code inside products only. @agent-use: Code uniqueness @agent-pattern: Exists check */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $b = $this->products->where('code', $code)->where('deleted_at', null);
        if ($excludeId) { $b->where('id !=', $excludeId); }
        return $b->countAllResults() > 0;
    }

    /** Cross-check product code against variant SKUs. @agent-use: Cross-table validation @agent-pattern: Prevent product/variant collision */
    public function codeExistsInVariants(string $code): bool
    {
        return $this->variants->where('sku', $code)->where('deleted_at', null)->countAllResults() > 0;
    }

    /** Map product => category ids. @agent-use: Attach categories @agent-pattern: Batch fetch */
    public function categoryMap(array $productIds): array { if (empty($productIds)) { return []; } $rows = $this->links->select('product_id, category_id')->whereIn('product_id', $productIds)->findAll(); $map = []; foreach ($rows as $row) { $map[$row['product_id']][] = (int) $row['category_id']; } return $map; }

    /** Variants grouped by product with images. @agent-use: Include variants @agent-pattern: Batch fetch */
    public function variantMap(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $rows = $this->variants->where('deleted_at', null)->whereIn('product_id', $productIds)->findAll();
        if (empty($rows)) {
            return [];
        }
        
        // Get variant IDs
        $variantIds = array_filter(array_column($rows, 'id'));
        
        // Fetch primary image for each variant
        $imageMap = [];
        if (!empty($variantIds)) {
            $images = $this->db->table('product_images')
                ->select('variant_id, image_url')
                ->whereIn('variant_id', $variantIds)
                ->where('deleted_at', null)
                ->orderBy('is_primary', 'DESC')
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getResultArray();
                
            foreach ($images as $img) {
                $vid = (int) $img['variant_id'];
                if (!isset($imageMap[$vid])) {
                    $imageMap[$vid] = $img['image_url'];
                }
            }
        }
        
        // Map variants by product, adding image_url
        $map = [];
        foreach ($rows as $row) {
            $vid = (int) $row['id'];
            $row['image_url'] = $imageMap[$vid] ?? ($row['image_url'] ?? null);
            $map[$row['product_id']][] = $row;
        }
        return $map;
    }

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

    /**
     * Attach existing images to product with duplicate guard.
     *
     * @agent-use: Attach images
     * @agent-pattern: Bulk update with skip list
     * @return array{attached_ids: int[], skipped_ids: int[], missing_ids: int[]}
     */
    public function attachImages(int $productId, array $imageIds): array
    {
        if (empty($imageIds)) {
            return ['attached_ids' => [], 'skipped_ids' => [], 'missing_ids' => []];
        }

        $ids = array_values(array_unique(array_map('intval', $imageIds)));
        $rows = $this->db->table('product_images')
            ->select('id, product_id, deleted_at')
            ->whereIn('id', $ids)
            ->get()
            ->getResultArray();

        $foundIds = array_map('intval', array_column($rows, 'id'));
        $missingIds = array_values(array_diff($ids, $foundIds));

        $alreadyAttached = [];
        $attachable = [];
        foreach ($rows as $row) {
            $rowId = (int) $row['id'];
            $currentProduct = isset($row['product_id']) ? (int) $row['product_id'] : null;
            $isActive = ($row['deleted_at'] ?? null) === null;

            if ($currentProduct === $productId && $isActive) {
                $alreadyAttached[] = $rowId;
            } else {
                $attachable[] = $rowId;
            }
        }

        if ($attachable) {
            $this->db->table('product_images')
                ->whereIn('id', $attachable)
                ->update([
                    'product_id' => $productId,
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

    /** Set primary image. @agent-use: Primary image @agent-pattern: Two-step update */
    public function setPrimaryImage(int $imageId): ?array { $image = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray(); if (! $image) { return null; } $this->db->table('product_images')->where('product_id', $image['product_id'])->update(['is_primary' => 0]); $this->db->table('product_images')->where('id', $imageId)->update(['is_primary' => 1]); return $image; }

    /** Delete image (soft/hard). @agent-use: Remove image @agent-pattern: Soft delete aware */
    public function deleteImage(int $imageId, bool $hard = false): void { $table = $this->db->table('product_images'); $hard ? $table->delete(['id' => $imageId]) : $table->where('id', $imageId)->update(['deleted_at' => $this->now()]); }

    /** Used attribute options for product. @agent-use: Attribute overview @agent-pattern: Join fetch */
    public function usedAttributeOptions(int $productId): array {
        $query = $this->db->table('product_attribute_values pav')
            ->select('pav.attribute_id, pav.option_id, pa.name as attribute_name, pa.type, pa.attribute_key, pa.attribute_key as slug, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, 1 as is_visible, pa.created_at, pa.updated_at')
            ->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.product_id', $productId)
            ->where('pav.deleted_at', null);
        
        $result = $query->get();
        return $result ? $result->getResultArray() : [];
    }

    /** Attribute values for product. @agent-use: Attribute listing @agent-pattern: Join fetch */
    public function productAttributeValues(int $productId): array {
        $query = $this->db->table('product_attribute_values pav')
            ->select('pav.*, pa.name as attribute_name, pa.type, pa.attribute_key, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, 1 as is_visible, pa.attribute_key as slug, null as attribute_values, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at')
            ->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.product_id', $productId)
            ->where('pav.deleted_at', null);
            
        $result = $query->get();
        return $result ? $result->getResultArray() : [];
    }

    /** Sync product-level attribute values. @agent-use: Update attributes @agent-pattern: Delete + batch insert */
    public function syncProductAttributeValues(int $productId, array $values): array { $this->db->table('product_attribute_values')->where('product_id', $productId)->where('variant_id', null)->delete(); if (empty($values)) { return []; } $rows = []; foreach ($values as $val) { if (empty($val['attribute_id'])) { continue; } $rows[] = ['product_id' => $productId, 'variant_id' => $val['variant_id'] ?? null, 'attribute_id' => $val['attribute_id'], 'option_id' => $val['option_id'] ?? null, 'value_text' => $val['value_text'] ?? null, 'created_at' => $this->now(), 'updated_at' => $this->now()]; } if (empty($rows)) { return []; } $this->db->table('product_attribute_values')->insertBatch($rows); return $rows; }

    /** Remove single attribute from product. @agent-use: Delete attribute @agent-pattern: Targeted delete */
    public function removeAttributeFromProduct(int $productId, int $attributeId): int { return $this->db->table('product_attribute_values')->where('product_id', $productId)->where('attribute_id', $attributeId)->delete(); }

    /** Total stock for analytics. @agent-use: Analytics @agent-pattern: Aggregate */
    public function totalStock(int $productId): float { $row = $this->db->table('product_variants_v2')->selectSum('stock_quantity')->where('product_id', $productId)->get()->getRowArray(); return (float) ($row['stock_quantity'] ?? 0); }

    private function applyFilters(array $filters)
    {
        // 1. Base Builder
        $b = $this->products->builder()
            ->select('products.*')
            ->where('products.deleted_at', null);

        // 2. Price List Logic
        // Logic V2:
        // - ID=1 (General) or NULL: Show ALL products (`products` table).
        // - ID>1 (Custom): Show ONLY added products (`inner join price_list_items`).
        
        $priceListId = !empty($filters['price_list_id']) ? (int)$filters['price_list_id'] : 1;

        // Always select products.selling_price as 'price' for "Bảng giá chung" column
        // Use a unique alias to avoid conflict with products.*
        $b->select("products.selling_price as original_price");

        if ($priceListId > 1) {
            // Custom Price List: INNER JOIN to show ONLY items in the list
            $b->join('price_list_items as pli', "pli.product_id = products.id AND pli.price_list_id = {$priceListId} AND pli.variant_id IS NULL", 'inner');
            // Select adjusted price from price_list_items
            $b->select("pli.price as adjusted_price");
        } else {
            // General Price List (ID=1) or None: Show all products
            // No adjusted price for general list - it's same as original
            $b->select("products.selling_price as adjusted_price");
        }
        
        // 3. Category Filter
        if (!empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $b->join('product_category_links as pcl', 'pcl.product_id = products.id', 'inner');
            $b->where('pcl.category_id', $catId);
        }

        // 4. Stock Status Filter
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $b->where('products.stock_quantity >', 0);
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $b->where('products.stock_quantity <=', 0);
            }
        }

        // 5. Search
        if (!empty($filters['search'])) {
            $b->groupStart()
                ->like('products.code', $filters['search'])
                ->orLike('products.name', $filters['search'])
                ->orLike('products.barcode', $filters['search'])
            ->groupEnd();
        }

        // 6. Status/Type
        if (!empty($filters['status'])) {
            $b->where('products.status', $filters['status']);
        }
        if (!empty($filters['product_type'])) {
            $b->where('products.product_type', $filters['product_type']);
        }

        // 7. Price Filters (Condition & Compare)
        if (!empty($filters['price_condition']) && !empty($filters['price_compare'])) {
            $operatorMap = [
                'lt' => '<',
                'lte' => '<=',
                'eq' => '=',
                'gt' => '>',
                'gte' => '>=',
            ];
            $op = $operatorMap[$filters['price_condition']] ?? null;
            
            // Map compare column
            $compareCol = ($filters['price_compare'] === 'cost' || $filters['price_compare'] === 'purchase') 
                ? 'products.purchase_price' 
                : 'products.purchase_price'; 

            if ($op) {
                // If priceListID > 1, we selected 'selling_price' from PLI, so alias usage in HAVING is safer
                // If ID=1, we selected 'products.selling_price', alias 'selling_price' might work or need literal.
                // Using HAVING is generally safer for aliased columns in CI4/MySQL.
                $b->having("selling_price {$op} {$compareCol}", null, false);
            }
        }

        return $b;
    }

    private function now(): string { return date('Y-m-d H:i:s'); }
}
