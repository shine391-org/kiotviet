<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductVariantV2Model;
use CodeIgniter\API\ResponseTrait;

class ProductVariantsController extends BaseController
{
    use ResponseTrait;

    protected ProductVariantV2Model $variants;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->variants = new ProductVariantV2Model();
        $this->db = \Config\Database::connect();
    }

    public function show($id)
    {
        $variant = $this->variants->find($id);
        if (!$variant) {
            return $this->failNotFound('Variant not found');
        }
        return $this->respond(['success' => true, 'data' => $variant]);
    }

    /**
     * POST /api/products/{productId}/variants
     */
    public function create($productId)
    {
        $data = $this->request->getJSON(true);
        $data['product_id'] = $productId;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->variants->insert($data);
        $id = $this->variants->getInsertID();
        return $this->respondCreated(['success' => true, 'data' => ['id' => $id] + $data]);
    }

    /**
     * PUT /api/variants/{id}
     */
    public function update($id)
    {
        $data = $this->request->getJSON(true);
        if (!$this->variants->find($id)) {
            return $this->failNotFound('Variant not found');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->variants->update($id, $data);
        return $this->respond(['success' => true]);
    }

    /**
     * DELETE /api/variants/{id} (soft)
     */
    public function delete($id)
    {
        if (!$this->variants->find($id)) {
            return $this->failNotFound('Variant not found');
        }
        $this->variants->delete($id);
        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/variants/{id}/upload-multiple
     * Lưu file vào writable/uploads/variants và trả về URLs.
     */
    public function uploadMultiple($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid variant id');
        }

        // Chấp nhận cả files và files[]
        $filesBag = $this->request->getFiles();
        $files = $filesBag['files'] ?? [];
        if ($files instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $files = [$files];
        }
        if (is_array($files) && isset($files[0]) && $files[0] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            // already an array of UploadedFile
        } elseif (!empty($filesBag)) {
            $files = array_values($filesBag);
        }
        if (empty($files)) {
            return $this->failValidationErrors('No files uploaded');
        }

        $uploadPath = WRITEPATH . 'uploads/variants';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $urls = [];
        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);
            $urls[] = '/uploads/variants/' . $newName;
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'variant_id' => $id,
                'files' => $urls,
            ],
        ]);
    }

    /**
     * POST /api/variants/{id}/images/attach-multiple
     * Body: { imageIds: [1,2,3] }
     * Gán các ảnh trong product_images vào variant
     */
    public function attachImages($id)
    {
        $payload = $this->request->getJSON(true);
        $imageIds = $payload['image_ids'] ?? $payload['imageIds'] ?? [];
        if (!is_array($imageIds) || empty($imageIds)) {
            return $this->failValidationErrors('imageIds required');
        }
        $this->db->table('product_images')
            ->whereIn('id', $imageIds)
            ->update(['variant_id' => $id]);

        return $this->respond(['success' => true, 'message' => 'Attached images to variant']);
    }

    /**
     * GET /api/variants/{id}/attribute-values
     */
    public function attributeValues($id)
    {
        $rows = $this->db->table('product_attribute_values pav')
            ->select('pav.*, pa.name AS attribute_name, pa.id as attribute_id, pa.type, pa.status, pa.sort_order, pa.slug, pa.is_required, pa.is_filterable, pa.group_name, pa.created_at as attribute_created_at, pa.updated_at as attribute_updated_at, pa.deleted_at as attribute_deleted_at, pa.parent_id as attribute_parent_id, pa.level as attribute_level, pa.code as attribute_code, pa.description as attribute_description, pa.unit as attribute_unit, pa.options as attribute_options, pa.display_type as attribute_display_type, pa.is_searchable as attribute_is_searchable, pa.is_used_for_variations as attribute_is_used_for_variations, pa.is_highlight as attribute_is_highlight, pa.meta as attribute_meta, pa.status as attribute_status, pa.is_system as attribute_is_system, pa.is_default as attribute_is_default, pa.position as attribute_position, pa.created_by as attribute_created_by, pa.updated_by as attribute_updated_by, pa.filterable as attribute_filterable, pa.comparable as attribute_comparable, pa.visibility as attribute_visibility, pa.required_at_checkout as attribute_required_at_checkout, pa.default_value as attribute_default_value, pa.help_text as attribute_help_text, pa.icon as attribute_icon, pa.tooltip as attribute_tooltip')
            ->join('product_attributes pa', 'pa.id = pav.attribute_id', 'left')
            ->where('pav.variant_id', $id)
            ->where('pav.deleted_at', null)
            ->get()->getResultArray();

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * POST /api/variants/{id}/attribute-values/sync
     */
    public function syncAttributeValues($id)
    {
        $payload = $this->request->getJSON(true);
        $values = $payload['attribute_values'] ?? [];

        if (!is_array($values)) {
            return $this->failValidationErrors('attribute_values must be array');
        }

        $this->db->table('product_attribute_values')
            ->where('variant_id', $id)
            ->delete();

        $rows = [];
        foreach ($values as $val) {
            if (empty($val['attribute_id'])) {
                continue;
            }
            $rows[] = [
                'product_id'  => $val['product_id'] ?? null,
                'variant_id'  => $id,
                'attribute_id'=> $val['attribute_id'],
                'option_id'   => $val['option_id'] ?? null,
                'value_text'  => $val['value_text'] ?? null,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($rows)) {
            $this->db->table('product_attribute_values')->insertBatch($rows);
        }

        return $this->respond(['success' => true, 'data' => $rows]);
    }

    /**
     * DELETE /api/attributes/remove-from-variant/{variantId}/{attributeId}
     */
    public function removeAttributeFromVariant($variantId, $attributeId)
    {
        $this->db->table('product_attribute_values')
            ->where('variant_id', $variantId)
            ->where('attribute_id', $attributeId)
            ->delete();

        return $this->respond(['success' => true, 'message' => 'Removed attribute from variant']);
    }

    /**
     * GET /api/variants/deleted
     */
    public function deletedList()
    {
        $productId = $this->request->getGet('product_id');
        $builder = $this->variants->onlyDeleted();
        if ($productId) {
            $builder->where('product_id', $productId);
        }
        $rows = $builder->findAll();
        return $this->respond(['success' => true, 'data' => ['variants' => $rows]]);
    }

    /**
     * PUT /api/variants/{id}/restore
     */
    public function restore($id)
    {
        $this->variants->update($id, ['deleted_at' => null]);
        return $this->respond(['success' => true]);
    }

    /**
     * DELETE /api/variants/{id}/hard
     */
    public function hardDelete($id)
    {
        $this->variants->delete($id, true);
        return $this->respond(['success' => true]);
    }
}
