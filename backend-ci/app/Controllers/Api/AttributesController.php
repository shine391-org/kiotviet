<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class AttributesController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // GET /api/attributes
    public function index()
    {
        $builder = $this->db->table('product_attributes')->where('deleted_at', null);

        if ($search = $this->request->getGet('search')) {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('attribute_key', $search)
                ->groupEnd();
        }
        if ($type = $this->request->getGet('type')) {
            $builder->where('type', $type);
        }
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }

        $data = $builder->orderBy('sort_order', 'ASC')->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    // GET /api/attributes/{id}
    public function show($id)
    {
        $row = $this->db->table('product_attributes')->where('id', $id)->get()->getRowArray();
        if (!$row) {
            return $this->failNotFound('Attribute not found');
        }
        return $this->respond(['success' => true, 'data' => $row]);
    }

    // POST /api/attributes
    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['name'])) {
            return $this->failValidationErrors('name is required');
        }
        helper('text');
        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? url_title($data['name'], '-', true),
            'attribute_key' => $data['attribute_key'] ?? uniqid('attr_'),
            'type' => $data['type'] ?? 'select',
            'is_required' => (int) ($data['is_required'] ?? 0),
            'is_filterable' => (int) ($data['is_filterable'] ?? 1),
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'is_visible' => $data['is_visible'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('product_attributes')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $this->respondCreated(['success' => true, 'data' => $payload]);
    }

    // PUT /api/attributes/{id}
    public function update($id)
    {
        $data = $this->request->getJSON(true);
        $payload = [];
        foreach (['name','slug','attribute_key','type','is_required','is_filterable','sort_order','status','is_visible'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }
        if (empty($payload)) {
            return $this->failValidationErrors('No data to update');
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('product_attributes')->where('id', $id)->update($payload);
        return $this->respond(['success' => true]);
    }

    // DELETE /api/attributes/{id}
    public function delete($id)
    {
        $this->db->table('product_attributes')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['success' => true]);
    }

    // GET /api/attributes/{id}/options
    public function options($attributeId)
    {
        $rows = $this->db->table('product_attribute_options')
            ->where('attribute_id', $attributeId)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $rows]);
    }

    // POST /api/attributes/{id}/options
    public function createOption($attributeId)
    {
        $data = $this->request->getJSON(true);
        if (empty($data['option_name'])) {
            return $this->failValidationErrors('option_name is required');
        }
        $payload = [
            'attribute_id' => $attributeId,
            'option_name' => $data['option_name'],
            'option_value' => $data['option_value'] ?? null,
            'color_code' => $data['color_code'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('product_attribute_options')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $this->respondCreated(['success' => true, 'data' => $payload]);
    }

    // PUT /api/attributes/options/{optionId}
    public function updateOption($optionId)
    {
        $data = $this->request->getJSON(true);
        $payload = [];
        foreach (['option_name','option_value','color_code','image_url','sort_order','status'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }
        if (empty($payload)) {
            return $this->failValidationErrors('No data to update');
        }
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('product_attribute_options')->where('id', $optionId)->update($payload);
        return $this->respond(['success' => true]);
    }

    // DELETE /api/attributes/options/{optionId}
    public function deleteOption($optionId)
    {
        $this->db->table('product_attribute_options')->delete(['id' => $optionId]);
        return $this->respond(['success' => true]);
    }

    // POST /api/attribute-values
    public function createValue()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['attribute_id'])) {
            return $this->failValidationErrors('attribute_id required');
        }
        $payload = [
            'product_id'  => $data['product_id'] ?? null,
            'variant_id'  => $data['variant_id'] ?? null,
            'attribute_id'=> $data['attribute_id'],
            'option_id'   => $data['option_id'] ?? null,
            'value_text'  => $data['value_text'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->table('product_attribute_values')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $this->respondCreated(['success' => true, 'data' => $payload]);
    }

    // DELETE /api/attribute-values/{id}
    public function deleteValue($id)
    {
        $this->db->table('product_attribute_values')->delete(['id' => $id]);
        return $this->respond(['success' => true]);
    }

    // GET /api/attributes/options/{optionId}/products
    public function productsByOption($optionId)
    {
        $rows = $this->db->table('product_attribute_values pav')
            ->select('pav.product_id, pav.variant_id')
            ->where('pav.option_id', $optionId)
            ->where('pav.deleted_at', null)
            ->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $rows]);
    }
}
