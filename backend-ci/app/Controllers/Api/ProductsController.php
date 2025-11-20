<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductCategoryLinkModel;
use App\Models\ProductVariantV2Model;
use CodeIgniter\API\ResponseTrait;

class ProductsController extends BaseController
{
    use ResponseTrait;

    protected ProductModel $products;
    protected ProductCategoryLinkModel $links;
    protected ProductVariantV2Model $variants;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->links    = new ProductCategoryLinkModel();
        $this->variants = new ProductVariantV2Model();
        $this->db = \Config\Database::connect();
    }

    /**
    * GET /api/products
    */
    public function index()
    {
        $page  = max(1, (int) $this->request->getGet('page'));
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 20));
        $offset= ($page - 1) * $limit;

        $builder = $this->products->where('deleted_at', null);

        // search by code or name
        if ($search = $this->request->getGet('search')) {
            $builder->like('code', $search)
                    ->orLike('name', $search)
                    ->orLike('barcode', $search);
        }

        // status filter
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }

        // product_type filter
        if ($ptype = $this->request->getGet('product_type')) {
            $builder->where('product_type', $ptype);
        }

        $total = $builder->countAllResults(false);
        $data  = $builder->orderBy('created_at', 'DESC')
                         ->limit($limit, $offset)
                         ->find();

        $includeVariants = (bool) $this->request->getGet('include_variants');
        // attach category ids (+ variants if requested)
        $ids = array_column($data, 'id');
        if (!empty($ids)) {
            $linkRows = $this->links->select('product_id, category_id')
                                    ->whereIn('product_id', $ids)
                                    ->findAll();
            $map = [];
            foreach ($linkRows as $row) {
                $map[$row['product_id']][] = (int) $row['category_id'];
            }

            $variantMap = [];
            if ($includeVariants) {
                $variantRows = $this->variants->whereIn('product_id', $ids)->findAll();
                foreach ($variantRows as $vr) {
                    $variantMap[$vr['product_id']][] = $vr;
                }
            }

            foreach ($data as &$row) {
                $row['category_ids'] = $map[$row['id']] ?? [];
                $row['variants'] = $variantMap[$row['id']] ?? [];
                $row['variants_v2'] = $row['variants']; // FE compatibility
            }
        }

        return $this->respond([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
    * GET /api/products/{id}
    */
    public function show($id = null)
    {
        $product = $this->products->find($id);
        if (!$product) {
            return $this->failNotFound('Product not found');
        }

        $linkRows = $this->links->select('category_id')->where('product_id', $id)->findAll();
        $product['category_ids'] = array_map(fn($r) => (int) $r['category_id'], $linkRows);
        $product['variants'] = $this->variants->where('product_id', $id)->findAll();
        $product['variants_v2'] = $product['variants'];

        return $this->respond([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * GET /api/products/{id}/detail-with-variants
     */
    public function detailWithVariants($id)
    {
        $product = $this->products->find($id);
        if (!$product) {
            return $this->failNotFound('Product not found');
        }
        $product['variants'] = $this->variants->where('product_id', $id)->findAll();
        $product['variants_v2'] = $product['variants'];
        return $this->respond(['success' => true, 'data' => $product]);
    }

    /**
     * GET /api/products/{id}/variants
     */
    public function variants($id)
    {
        $variants = $this->variants->where('product_id', $id)->findAll();
        return $this->respond([
            'success' => true,
            'data' => $variants,
        ]);
    }

    /**
     * POST /api/products
     */
    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['code']) || empty($data['name'])) {
            return $this->failValidationErrors('code và name là bắt buộc');
        }
        $payload = $data;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->products->insert($payload);
        $id = $this->products->getInsertID();
        return $this->respondCreated(['success' => true, 'data' => ['id' => $id] + $payload]);
    }

    /**
     * PUT /api/products/{id}
     */
    public function update($id)
    {
        $data = $this->request->getJSON(true);
        if (!$this->products->find($id)) {
            return $this->failNotFound('Product not found');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->products->update($id, $data);
        return $this->respond(['success' => true]);
    }

    /**
     * DELETE /api/products/{id}
     */
    public function delete($id)
    {
        if (!$this->products->find($id)) {
            return $this->failNotFound('Product not found');
        }
        $this->products->delete($id); // soft delete enabled
        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/products/check-code
     */
    public function checkCode()
    {
        $code = $this->request->getPost('code');
        $exclude = $this->request->getPost('exclude_id');
        if (!$code) {
            return $this->failValidationErrors('code is required');
        }
        $builder = $this->db->table('products')->where('code', $code);
        if ($exclude) {
            $builder->where('id !=', $exclude);
        }
        $exists = $builder->where('deleted_at', null)->countAllResults() > 0;
        return $this->respond(['success' => true, 'exists' => $exists]);
    }

    /**
     * GET /api/products/{id}/images
     */
    public function images($id)
    {
        $rows = $this->db->table('product_images')
            ->where('product_id', $id)
            ->where('deleted_at', null)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * POST /api/products/upload-multiple
     */
    public function uploadMultiple()
    {
        $productId = (int) ($this->request->getPost('product_id') ?? 0);
        if ($productId <= 0) {
            return $this->failValidationErrors('product_id is required');
        }

        $filesBag = $this->request->getFiles();
        $files = $filesBag['files'] ?? [];
        if ($files instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $files = [$files];
        }
        if (empty($files)) {
            return $this->failValidationErrors('No files uploaded');
        }

        $uploadPath = WRITEPATH . 'uploads/products';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $rows = [];
        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);
            $rows[] = [
                'product_id' => $productId,
                'variant_id' => null,
                'image_path' => '/uploads/products/' . $newName,
                'image_url'  => '/uploads/products/' . $newName,
                'is_primary' => 0,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'file_name'  => $file->getClientName(),
            ];
        }

        if (!empty($rows)) {
            $this->db->table('product_images')->insertBatch($rows);
        }

        return $this->respond([
            'success' => true,
            'uploaded_count' => count($rows),
            'data' => $rows,
        ]);
    }

    /**
     * POST /api/products/upload (single)
     */
    public function uploadSingle()
    {
        $productId = (int) ($this->request->getPost('product_id') ?? 0);
        if ($productId <= 0) {
            return $this->failValidationErrors('product_id is required');
        }
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors('file is required');
        }
        $uploadPath = WRITEPATH . 'uploads/products';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }
        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);
        $row = [
            'product_id' => $productId,
            'variant_id' => null,
            'image_path' => '/uploads/products/' . $newName,
            'image_url'  => '/uploads/products/' . $newName,
            'is_primary' => 0,
            'sort_order' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'file_name'  => $file->getClientName(),
        ];
        $this->db->table('product_images')->insert($row);
        $row['id'] = $this->db->insertID();
        return $this->respond(['success' => true, 'data' => $row]);
    }

    /**
     * POST /api/products/{id}/images/attach-multiple
     */
    public function attachImages($id)
    {
        $payload = $this->request->getJSON(true);
        $imageIds = $payload['image_ids'] ?? $payload['imageIds'] ?? [];
        if (!is_array($imageIds) || empty($imageIds)) {
            return $this->failValidationErrors('image_ids required');
        }

        $this->db->table('product_images')
            ->whereIn('id', $imageIds)
            ->update(['product_id' => $id, 'deleted_at' => null]);

        return $this->respond([
            'success' => true,
            'message' => 'Gắn ảnh thành công',
            'attached_count' => count($imageIds),
        ]);
    }

    /**
     * PUT /api/products/images/{id}/set-primary
     */
    public function setPrimaryImage($imageId)
    {
        $image = $this->db->table('product_images')->where('id', $imageId)->get()->getRowArray();
        if (!$image) {
            return $this->failNotFound('Image not found');
        }
        $productId = $image['product_id'];
        $this->db->table('product_images')->where('product_id', $productId)->update(['is_primary' => 0]);
        $this->db->table('product_images')->where('id', $imageId)->update(['is_primary' => 1]);
        return $this->respond(['success' => true]);
    }

    /**
     * DELETE /api/products/images/{id}
     */
    public function deleteImage($imageId)
    {
        $hard = (bool) $this->request->getGet('hard');
        if ($hard) {
            $this->db->table('product_images')->delete(['id' => $imageId]);
        } else {
            $this->db->table('product_images')->where('id', $imageId)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->respond(['success' => true]);
    }

    /**
     * GET /api/products/{id}/used-attribute-options
     */
    public function usedAttributeOptions($id)
    {
        $rows = $this->db->table('product_attribute_values pav')
            ->select('pav.attribute_id, pav.option_id, pa.name as attribute_name, pa.type, pa.attribute_key, pa.slug, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, pa.is_visible, pa.created_at, pa.updated_at')
            ->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.product_id', $id)
            ->where('pav.deleted_at', null)
            ->get()->getResultArray();

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * GET /api/products/{id}/attribute-values
     */
    public function productAttributeValues($id)
    {
        $rows = $this->db->table('product_attribute_values pav')
            ->select('pav.*, pa.name as attribute_name, pa.type, pa.attribute_key, pa.sort_order, pa.status, pa.is_filterable, pa.is_required, pa.is_visible, pa.slug, pa.attribute_values, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at, pa.attribute_key')
            ->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.product_id', $id)
            ->where('pav.deleted_at', null)
            ->get()->getResultArray();

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * POST /api/products/{id}/attribute-values
     */
    public function updateProductAttributeValues($id)
    {
        $payload = $this->request->getJSON(true);
        $values = $payload['attribute_values'] ?? [];
        if (!is_array($values)) {
            return $this->failValidationErrors('attribute_values must be array');
        }

        // clear old values for product-level
        $this->db->table('product_attribute_values')
            ->where('product_id', $id)
            ->where('variant_id', null)
            ->delete();

        $rows = [];
        foreach ($values as $val) {
            if (empty($val['attribute_id'])) {
                continue;
            }
            $rows[] = [
                'product_id'   => $id,
                'variant_id'   => $val['variant_id'] ?? null,
                'attribute_id' => $val['attribute_id'],
                'option_id'    => $val['option_id'] ?? null,
                'value_text'   => $val['value_text'] ?? null,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }
        if (!empty($rows)) {
            $this->db->table('product_attribute_values')->insertBatch($rows);
        }

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * DELETE /api/products/{productId}/attribute-values/{attributeId}
     */
    public function removeAttributeFromProduct($productId, $attributeId)
    {
        $this->db->table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', $attributeId)
            ->delete();

        return $this->respond(['success' => true, 'message' => 'Removed attribute from product']);
    }

    /**
     * POST /api/products/import (stub)
     */
    public function import()
    {
        // Lưu file để kiểm tra, chưa xử lý parsing
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors('file is required');
        }
        $uploadPath = WRITEPATH . 'imports';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }
        $file->move($uploadPath, $file->getRandomName());
        return $this->respond(['success' => true, 'data' => ['imported' => 0, 'failed' => 0, 'errors' => []]]);
    }

    /**
     * GET /api/products/export (simple CSV)
     */
    public function export()
    {
        $rows = $this->db->table('products')->where('deleted_at', null)->limit(500)->get()->getResultArray();
        $csv = "id,code,name,price\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s,%s\n", $r['id'], $r['code'], $r['name'], $r['selling_price']);
        }
        return $this->response->setHeader('Content-Type', 'text/csv')->setBody($csv);
    }

    /**
     * GET /api/products/{id}/analytics (simple stub)
     */
    public function analytics($id)
    {
        $product = $this->products->find($id);
        if (!$product) {
            return $this->failNotFound('Product not found');
        }
        $totalStock = $this->db->table('product_variants_v2')->where('product_id', $id)->selectSum('stock_quantity')->get()->getRowArray()['stock_quantity'] ?? 0;
        return $this->respond([
            'success' => true,
            'data' => [
                'total_stock' => (float) $totalStock,
                'total_variant' => $this->variants->where('product_id', $id)->countAllResults(),
            ],
        ]);
    }
}
