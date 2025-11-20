<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Attribute Model
 * Quản lý thuộc tính sản phẩm (attributes, options, values)
 * ✅ FIXED: Complete field mapping with DB schema
 */
class Product_attribute_model extends CI_Model {
    
    private $attributes_table = 'product_attributes';
    private $options_table = 'product_attribute_options';
    private $values_table = 'product_attribute_values';
    
    public function __construct() {
        parent::__construct();
    }
    
    // ============================================
    // ATTRIBUTES (Nhóm thuộc tính: Màu sắc, Size)
    // ============================================
    
    /**
     * Get all attributes
     * ✅ FIXED: Add all fields from schema
     */
    /*public function get_all_attributes($params = []) {
        // ✅ FIX: Explicit select all fields
        $this->db->select('
            id, name, slug, attribute_key, type,
            is_required, is_filterable, attribute_values,
            sort_order, status, is_visible,
            created_at, created_by, updated_at, updated_by, deleted_at
        ');
        
        //$this->db->where('status', 'active');
        
        if (!empty($params['search'])) {
            $this->db->like('name', $params['search']);
        }
        
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->attributes_table);
        return $query->result_array();
    }
    */
    
    /**
     * Get attribute by ID
     * ✅ FIXED: Complete field list
     */
    public function get_attribute_by_id($id) {
        $this->db->select('
            id, name, slug, attribute_key, type,
            is_required, is_filterable, attribute_values,
            sort_order, status, is_visible,
            created_at, created_by, updated_at, updated_by, deleted_at
        ');
        
        $this->db->where('id', $id);
        $result = $this->db->get($this->attributes_table)->row_array();
        
        if ($result) {
            // ✅ Cast boolean fields
            $result['is_required'] = (int)($result['is_required'] ?? 0);
            $result['is_filterable'] = (int)($result['is_filterable'] ?? 1);
            $result['is_visible'] = (int)($result['is_visible'] ?? 1);
        }
        
        error_log('🔵 GET_ATTRIBUTE_BY_ID #' . $id . ': ' . json_encode($result));
        
        return $result;
    }
    
    /**
     * Create attribute
     * ✅ FIXED: Add all fields
     */
    public function create_attribute($data) {
        $insert_data = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'attribute_key' => $data['attribute_key'] ?? strtolower(str_replace(' ', '_', $data['name'])),
            'type' => $data['type'] ?? 'text',
            'is_required' => isset($data['is_required']) ? (int)$data['is_required'] : 0,
            'is_filterable' => isset($data['is_filterable']) ? (int)$data['is_filterable'] : 1,
            'attribute_values' => $data['attribute_values'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'is_visible' => isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null,
        ];
        
        error_log('🔵 CREATE_ATTRIBUTE: ' . json_encode($insert_data));
        
        $this->db->insert($this->attributes_table, $insert_data);
        return $this->db->insert_id();
    }
    
    /**
     * Update attribute
     * ✅ FIXED: Complete field whitelist
     */
    public function update_attribute($id, $data) {
        $existing = $this->db->where('id', $id)->get($this->attributes_table)->row_array();
        if (!$existing) {
            error_log("❌ ATTRIBUTE #$id NOT FOUND");
            return false;
        }
        
        error_log("📥 BEFORE UPDATE #$id: " . json_encode($existing));
        
        // ✅ Build update data
        $update_data = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $data['updated_by'] ?? null,
        ];
        
        // ✅ FIX: Complete whitelist
        $allowed_fields = [
            'name', 'slug', 'attribute_key', 'type',
            'is_required', 'is_filterable', 'attribute_values',
            'sort_order', 'status', 'is_visible'
        ];
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                // ✅ Cast boolean fields
                if (in_array($field, ['is_required', 'is_filterable', 'is_visible'])) {
                    $update_data[$field] = (int)$data[$field];
                } else {
                    $update_data[$field] = $data[$field];
                }
            }
        }
        
        error_log("📤 UPDATE DATA #$id: " . json_encode($update_data));
        
        $result = $this->db->where('id', $id)->update($this->attributes_table, $update_data);
        
        error_log("✅ UPDATE RESULT: " . ($result ? 'SUCCESS' : 'FAILED') . " | affected_rows: " . $this->db->affected_rows());
        
        return $result;
    }

    /**
     * Lấy toàn bộ attributes kèm usage_count (đếm cả variant soft-deleted)
     * @param array $params
     * @return array
     */
    public function get_all_attributes_with_usage($params = []) {
        $this->db->select('
            a.id, a.name, a.slug, a.attribute_key, a.type,
            a.is_required, a.is_filterable, a.attribute_values,
            a.sort_order, a.status, a.is_visible,
            a.created_at, a.created_by, a.updated_at, a.updated_by, a.deleted_at,
            COALESCE(usage_counts.count_all, 0) AS usage_count,
            COALESCE(usage_counts.count_active, 0) AS usage_count_active
        ');
        $this->db->from('product_attributes a');
        
        // --- Subquery tổng số mapping tới attribute_id (không lọc deleted) ---
        $this->db->join(
            '(SELECT 
                  attribute_id, 
                  COUNT(*) as count_all,
                  SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as count_active
              FROM product_attribute_values
              GROUP BY attribute_id
            ) AS usage_counts',
            'usage_counts.attribute_id = a.id',
            'left'
        );
        
        if (!empty($params['search'])) {
            $this->db->like('a.name', $params['search']);
        }
        $this->db->order_by('a.sort_order', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }        

    /**
     * Lấy danh sách sản phẩm/biến thể sử dụng attribute
     * @param int $attribute_id
     * @param bool $include_deleted - Có lấy cả variant bị soft-delete không
     * @return array
     */
    public function get_products_by_attribute($attribute_id, $show_all = true) {
        $this->db->select('
            p.id as product_id,
            p.code as product_code,
            p.name as product_name,
            p.image as product_image,
            v.id as variant_id,
            v.sku as variant_sku,
            v.variant_name,
            v.price as variant_price,
            v.stock_quantity as variant_stock,
            v.deleted_at as variant_deleted_at,
            pav.deleted_at as mapping_deleted_at,
            pav.value_text,
            pav.option_id,
            pao.option_name
        ');
        $this->db->from('product_attribute_values pav');
        $this->db->join('products p', 'p.id = pav.product_id', 'left');
        $this->db->join('product_variants_v2 v', 'v.id = pav.variant_id', 'left');
        $this->db->join('product_attribute_options pao', 'pao.id = pav.option_id', 'left');
        $this->db->where('pav.attribute_id', (int)$attribute_id);
    
        // Hiện toàn bộ (cả mapping đã xóa mềm + variant đã xóa mềm) hoặc chỉ active
        if (!$show_all) {
            $this->db->where('v.deleted_at IS NULL');
            $this->db->where('pav.deleted_at IS NULL');
        }
        $this->db->order_by('p.id', 'ASC');
        $this->db->order_by('v.id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }    

    /**
     * Lấy danh sách sản phẩm/biến thể sử dụng option
     */
    public function get_products_by_option($option_id, $include_deleted = true) {
        $this->db->select('
            p.id as product_id,
            p.code as product_code,
            p.name as product_name,
            p.image as product_image,
            v.id as variant_id,
            v.sku as variant_sku,
            v.variant_name,
            v.price as variant_price,
            v.stock_quantity as variant_stock,
            v.deleted_at as variant_deleted_at,
            pav.deleted_at as mapping_deleted_at,
            pav.value_text,
            pao.option_name
        ');
        
        $this->db->from('product_attribute_values pav');
        $this->db->join('products p', 'p.id = pav.product_id', 'left');
        $this->db->join('product_variants_v2 v', 'v.id = pav.variant_id', 'left');
        $this->db->join('product_attribute_options pao', 'pao.id = pav.option_id', 'left');
        
        $this->db->where('pav.option_id', (int)$option_id);
    
        if (!$include_deleted) {
            // Chỉ lấy các mapping và variant còn sống
            $this->db->where('pav.deleted_at IS NULL');
            $this->db->group_start();
            $this->db->where('v.id IS NULL');
            $this->db->or_where('v.deleted_at IS NULL');
            $this->db->group_end();
        }
        // Nếu include_deleted == true => không lọc gì, trả ra cả mapping và variant đã xoá mềm
        
        $this->db->order_by('p.id', 'ASC');
        $this->db->order_by('v.id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }    

    /**
     * Đếm số lượng sản phẩm/variant dùng attribute (bao gồm cả variant soft-deleted)
     * @param int $attribute_id
     * @return int
     */
    public function count_products_using_attribute($attribute_id) {
        $this->db->from('product_attribute_values pav');
        $this->db->where('pav.attribute_id', $attribute_id);
        // KHÔNG filter pav.deleted_at => đếm cả soft delete
        return $this->db->count_all_results();
    }

    /**
     * Đếm số lượng sản phẩm/variant dùng option (bao gồm cả variant soft-deleted)
     * @param int $option_id
     * @return int
     */
    public function count_products_using_option($option_id) {
        $this->db->from('product_attribute_values pav');
        $this->db->where('pav.option_id', $option_id);
        // KHÔNG filter pav.deleted_at
        return $this->db->count_all_results();
    }
  
    /**
     * Delete attribute (soft delete)
     */
    // Sửa lại logic xóa attribute cho chuẩn
    public function delete_attribute($id) {
        $count = $this->count_products_using_attribute($id);
        if ($count > 0) {
            return [
                'success' => false,
                'message' => "Không thể xóa thuộc tính này vì đang được sử dụng bởi $count sản phẩm/biến thể",
                'usage_count' => $count
            ];
        }
        $result = $this->db->where('id', $id)->update($this->attributes_table, [
            'status' => 'inactive',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        if ($result) {
            return ['success' => true, 'message' => 'Xóa thuộc tính thành công'];
        }
        return ['success' => false, 'message' => 'Lỗi khi xóa thuộc tính'];
    }
    
    // ============================================
    // OPTIONS (Giá trị: Đen, Nâu, Navy, To, Nhỏ)
    // ============================================
    
    /**
     * Get options by attribute_id
     * ✅ FIXED: Complete field list
     */
    public function get_options_by_attribute($attribute_id) {
        $this->db->select('
            id, attribute_id, option_name, option_value,
            color_code, image_url, sort_order, status,
            created_at, created_by, updated_at, updated_by
        ');
        
        $this->db->where('attribute_id', $attribute_id);
        $this->db->where('status', 'active');
        $this->db->order_by('sort_order', 'ASC');
        
        $query = $this->db->get($this->options_table);
        return $query->result_array();
    }
    
    /**
     * Get option by ID
     * ✅ FIXED: Complete field list
     */
    public function get_option_by_id($id) {
        $this->db->select('
            id, attribute_id, option_name, option_value,
            color_code, image_url, sort_order, status,
            created_at, created_by, updated_at, updated_by
        ');
        
        $query = $this->db->where('id', $id)->get($this->options_table);
        return $query->row_array();
    }
    
    /**
     * Create option
     * ✅ FIXED: Use option_name instead of value_text
     */
    public function create_option($data) {
        $insert_data = [
            'attribute_id' => $data['attribute_id'],
            'option_name' => $data['option_name'],  // ✅ ĐÚNG FIELD
            'option_value' => $data['option_value'] ?? strtolower(str_replace(' ', '_', $data['option_name'])),
            'color_code' => $data['color_code'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $data['created_by'] ?? null,
        ];
        
        error_log('🔵 CREATE_OPTION: ' . json_encode($insert_data));
        
        $this->db->insert($this->options_table, $insert_data);
        return $this->db->insert_id();
    }
    
    /**
     * Update option
     * ✅ FIXED: Complete whitelist
     */
    public function update_option($id, $data) {
        $update_data = [
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $data['updated_by'] ?? null,
        ];
        
        $allowed_fields = ['option_name', 'option_value', 'color_code', 'image_url', 'sort_order', 'status'];
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        // ✅ Auto-generate option_value
        if (isset($data['option_name']) && !isset($data['option_value'])) {
            $update_data['option_value'] = strtolower(str_replace(' ', '_', $data['option_name']));
        }
        
        error_log('🔵 UPDATE_OPTION #' . $id . ': ' . json_encode($update_data));
        
        return $this->db->where('id', $id)->update($this->options_table, $update_data);
    }
    
    /**
     * Delete option
     */
    // Sửa lại logic xóa option cho chuẩn
    public function delete_option($id) {
        $count = $this->count_products_using_option($id);
        if ($count > 0) {
            return [
                'success' => false,
                'message' => "Không thể xóa giá trị này vì đang được sử dụng bởi $count sản phẩm/biến thể",
                'usage_count' => $count
            ];
        }
        $result = $this->db->where('id', $id)->delete($this->options_table);
        if ($result) {
            return ['success' => true, 'message' => 'Xóa giá trị thành công'];
        }
        return ['success' => false, 'message' => 'Lỗi khi xóa giá trị'];
    }
    
    // ============================================
    // VALUES (Gán thuộc tính vào product/variant)
    // ============================================
    
    /**
     * Get attribute values by product_id (simple product)
     * ✅ FIXED: Complete JOIN with correct field names
     */
    public function get_values_by_product($product_id) {
        $this->db->select('
            v.id, v.product_id, v.variant_id, v.attribute_id,
            v.option_id, v.value_text,
            a.name as attribute_name, a.type as attribute_type,
            o.option_name, o.option_value, o.color_code, o.image_url
        ');
        
        $this->db->from("{$this->values_table} v");
        $this->db->join("{$this->attributes_table} a", 'a.id = v.attribute_id', 'left');
        $this->db->join("{$this->options_table} o", 'o.id = v.option_id', 'left');
        $this->db->where('v.product_id', $product_id);
        $this->db->where('v.variant_id IS NULL', null, false);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Lấy danh sách attributes áp dụng cho product (có options)
     */
    public function get_by_product($product_id) {
        // Lấy attributes có liên quan đến product, kèm options
        $attributes = $this->db
            ->where('product_id', $product_id)
            ->where('deleted_at IS NULL', null, false)
            ->get('product_attributes')
            ->result_array();

        foreach ($attributes as &$attr) {
            $attr['options'] = $this->db
                ->where('attribute_id', $attr['id'])
                ->where('deleted_at IS NULL', null, false)
                ->get('product_attribute_options')
                ->result_array();
        }

        return $attributes;
    }
    
    /**
     * Lấy attribute values kèm thông tin attribute, option theo variant_id
     */
    public function get_values_by_variant($variant_id) {
        $this->db->select('
            v.id, v.product_id, v.variant_id, v.attribute_id,
            v.option_id, v.value_text,
            a.name as attribute_name, a.type as attribute_type,
            o.option_name, o.option_value, o.color_code, o.image_url
        ');
        
        $this->db->from("{$this->values_table} v");
        $this->db->join("{$this->attributes_table} a", 'a.id = v.attribute_id', 'left');
        $this->db->join("{$this->options_table} o", 'o.id = v.option_id', 'left');
        $this->db->where('v.variant_id', $variant_id);
        
        // ✅ FIX: Flexible deleted_at check
        $this->db->where('(v.deleted_at IS NULL OR v.deleted_at = "0000-00-00 00:00:00")', null, false);
        
        $query = $this->db->get();
        
        // ✅ Debug log
        error_log('🔍 GET_VALUES_BY_VARIANT SQL: ' . $this->db->last_query());
        error_log('📊 RESULT COUNT: ' . $query->num_rows());
        
        return $query->result_array();
    }    

    /**
     * Thêm hàng loạt giá trị attribute-values
     */
    public function insert_values_batch($data) {
        if (empty($data)) return;
        $this->db->insert_batch($this->values_table, $data);
    }
    
    /**
     * Create attribute value
     * ✅ FIXED: Complete fields
     */
    public function create_value($data) {
        $insert_data = [
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'attribute_id' => $data['attribute_id'],
            'option_id' => $data['option_id'] ?? null,
            'value_text' => $data['value_text'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    
        // Check duplicate
        $this->db->where('product_id', $insert_data['product_id']);
        $this->db->where('attribute_id', $insert_data['attribute_id']);
    
        if ($insert_data['variant_id']) {
            $this->db->where('variant_id', $insert_data['variant_id']);
        } else {
            $this->db->where('variant_id IS NULL', null, false);
        }
    
        $existing = $this->db->get($this->values_table)->row_array();
    
        if ($existing) {
            error_log('⚠️ DUPLICATE VALUE - Updating instead');
            // Update existing VÀ RESET deleted_at!
            return $this->db->where('id', $existing['id'])->update($this->values_table, [
                'option_id' => $insert_data['option_id'],
                'value_text' => $insert_data['value_text'],
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null, // <<< RESET deleted_at về null tại đây!
            ]);
        }
    
        error_log('🔵 CREATE_VALUE: ' . json_encode($insert_data));
    
        $insert_data['deleted_at'] = null; // Phòng trường hợp DB default != null
        $this->db->insert($this->values_table, $insert_data);
        return $this->db->insert_id();
    }    
    
    /**
     * Delete attribute value
     */
    public function delete_value($id) {
        return $this->db->where('id', $id)->delete($this->values_table);
    }
    
    /**
     * Xóa mềm các giá trị attribute-values của biến thể theo variant_id
     */
    public function delete_values_by_variant($variant_id) {
        return $this->db
            ->where('variant_id', $variant_id)
            ->update($this->values_table, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function remove_attribute_from_variant($variant_id, $attribute_id) {
        $this->db->where('variant_id', $variant_id);
        $this->db->where('attribute_id', $attribute_id);
        return $this->db->delete('product_attribute_values');
    }    
    
    /**
     * Delete all values by product_id
     */
    public function delete_values_by_product($product_id) {
        return $this->db->where('product_id', $product_id)
                        ->where('variant_id IS NULL', null, false)
                        ->delete($this->values_table);
    }
    
    /**
     * Sync values for variant (batch update)
     * ✅ FIXED: Complete implementation
     */
    public function sync_variant_values($variant_id, $product_id, $values) {
        $this->db->trans_start();
        
        // Delete existing
        $this->delete_values_by_variant($variant_id);
        
        // Insert new
        foreach ($values as $val) {
            $type = $val['type'] ?? $val['attribute_type'] ?? null;
        
            if (in_array($type, ['select', 'color', 'image']) && empty($val['option_id'])) {
                continue;
            }
        
            // Tìm xem bản ghi đã tồn tại chưa
            $existing = $this->db->from('product_attribute_values')
                ->where('product_id', $product_id)
                ->where('variant_id', $variant_id)
                ->where('attribute_id', $val['attribute_id'])
                ->where('option_id', $val['option_id'] ?? null)
                ->get()
                ->row_array();
        
            if ($existing) {
                // Update bản ghi hiện tại
                $this->db->where('id', $existing['id'])
                    ->update('product_attribute_values', [
                        'value_text' => $val['value_text'] ?? null,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'deleted_at' => null,
                    ]);
            } else {
                // Insert mới
                $this->create_value([
                    'product_id' => $product_id,
                    'variant_id' => $variant_id,
                    'attribute_id' => $val['attribute_id'],
                    'option_id' => $val['option_id'] ?? null,
                    'value_text' => $val['value_text'] ?? null,
                ]);
            }
        }        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    /**
     * Sync values for product (simple product without variants)
     * ✅ NEW: Add this function
     */
    public function sync_product_values($product_id, $values) {
        $this->db->trans_start();
    
        $this->db->where('product_id', $product_id)->delete('product_attribute_values');
    
        if (!is_array($values) || empty($values)) {
            $this->db->trans_complete();
            return $this->db->trans_status();
        }
    
        foreach ($values as $val) {
            $data = [
                'product_id'   => (int)$product_id,
                'variant_id'   => null,
                'attribute_id' => isset($val['attribute_id']) ? (int)$val['attribute_id'] : null,
                'option_id'    => isset($val['option_id']) ? (int)$val['option_id'] : null,
                'value_text'   => isset($val['value_text']) ? $val['value_text'] : null,
            ];
            // Bắt buộc phải có product_id và attribute_id
            if (empty($data['product_id']) || empty($data['attribute_id'])) continue;
    
            // Log query để debug lỗi
            $ok = $this->db->insert('product_attribute_values', $data);
            if (!$ok) error_log('INSERT FAIL SQL: '.$this->db->last_query());
        }
    
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    /**
     * Sinh tổ hợp các combination từ mảng attribute_values
     * @param int $product_id
     * @param array $attribute_values - mỗi phần tử là row chứa attribute_id, option_id, option_name
     * @return array combinations, mỗi phần tử gồm: attribute_value_ids, sku, name, price, cost_price
     */
    public function generate_combinations($product_id, $attribute_values) {
        // Group theo attribute_id
        $grouped = [];
        foreach ($attribute_values as $val) {
            $grouped[$val['attribute_id']][] = $val;
        }

        // Sinh tổ hợp
        $result = [[]];
        foreach ($grouped as $attribute_id => $values) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($values as $value) {
                    $temp[] = array_merge($combination, [$value]);
                }
            }
            $result = $temp;
        }

        // Format output
        $formatted = [];
        foreach ($result as $combo) {
            $attribute_value_ids = array_column($combo, 'option_id');
            $names = array_column($combo, 'option_name');

            $formatted[] = [
                'attribute_value_ids' => $attribute_value_ids,
                'sku' => $product_id . '-' . implode('-', $attribute_value_ids),
                'name' => implode(' / ', $names),
                'price' => 0,
                'cost_price' => 0
            ];
        }

        return $formatted;
    }
    /**
     * Tạo biến thể từ attribute values của sản phẩm
     */
    public function generate_variants_from_attributes($product_id, $attribute_values) {
        $this->db->trans_start();
    
        // 1. Cập nhật sản phẩm thành biến thể (has_variants = 1)
        $this->db->where('id', $product_id);
        $this->db->update('products', ['has_variants' => 1]);
    
        // 2. Xóa các biến thể cũ (nếu có)
        $this->db->where('product_id', $product_id);
        $this->db->delete('product_variants_v2');
    
        // 3. XÓA thuộc tính cũ trên product cha, để đảm bảo không duplicate khi insert cho variant
        $this->db->where('product_id', $product_id);
        $this->db->delete('product_attribute_values');
    
        // 4. Sinh tổ hợp biến thể
        $variants = $this->generate_combinations($attribute_values);
        foreach ($variants as $variant) {
            // a) Insert bản ghi mới vào product_variants_v2
            $sku = $product_id . '-' . implode('-', array_column($variant, 'option_value'));
            $this->db->insert('product_variants_v2', [
                'product_id'    => $product_id,
                'sku'           => $sku,
                'variant_name'  => implode(', ', array_column($variant, 'option_name')),
                'price'         => 0,
                'stock_quantity'=> 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $variant_id = $this->db->insert_id();
    
            // b) Gán attribute values cho biến thể
            foreach ($variant as $value) {
                $this->db->insert('product_attribute_values', [
                    'product_id'   => $product_id,
                    'variant_id'   => $variant_id,
                    'attribute_id' => $value['attribute_id'],
                    'option_id'    => $value['option_id'],
                    'value_text'   => $value['value_text'],
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
    
        $this->db->trans_complete();
        return $this->db->trans_status();
    }       

    /**
     * Xóa tất cả giá trị thuộc tính của sản phẩm theo product_id và attribute_id
     */
    public function remove_attribute_from_product($product_id, $attribute_id) {
        $this->db->where('product_id', (int)$product_id);
        $this->db->where('attribute_id', (int)$attribute_id);
        return $this->db->delete('product_attribute_values');
    }


    /**
     * ✅ NEW LOGIC: Generate hoặc restore variants từ attributes
     * @param int $product_id
     * @param array $attribute_combinations - Mảng combo attributes
     * @return bool
     */
    public function generate_or_restore_variants($product_id, $attribute_combinations) {
        $this->db->trans_start();
    
        // Load Product_variant_model cho restore_with_attributes
        $this->load->model('Product_variant_model');
    
        foreach ($attribute_combinations as $combo) {
            // $combo format: ['attribute_value_ids' => [1, 5], 'sku' => '...', 'name' => '...', 'price' => ...]
            $option_ids = $combo['attribute_value_ids'];
            $variant_signature = $this->build_variant_signature($option_ids);
    
            // Kiểm tra tồn tại (kể cả soft-deleted)
            $existing_variant = $this->db
                ->where('product_id', $product_id)
                ->where('variant_signature', $variant_signature)
                ->get('product_variants_v2')
                ->row_array();
    
            if ($existing_variant) {
                // Đã tồn tại
                $variant_id = $existing_variant['id'];
    
                if ($existing_variant['deleted_at'] !== NULL) {
                    // CASE: Soft-deleted → RESTORE + update meta
                    $this->Product_variant_model->restore_with_attributes($variant_id);
    
                    $this->db->where('id', $variant_id)->update('product_variants_v2', [
                        'sku' => $combo['sku'],
                        'variant_name' => $combo['name'],
                        'price' => $combo['price'] ?? $existing_variant['price'],
                        'cost_price' => $combo['cost_price'] ?? $existing_variant['cost_price'],
                        'variant_signature' => $variant_signature,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    log_message('info', "✅ Restored variant ID: {$variant_id}");
                } else {
                    // CASE: Đang active → Update meta (option_id vẫn giữ nguyên)
                    $this->db->where('id', $variant_id)->update('product_variants_v2', [
                        'sku' => $combo['sku'],
                        'variant_name' => $combo['name'],
                        'price' => $combo['price'] ?? $existing_variant['price'],
                        'variant_signature' => $variant_signature,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    log_message('info', "✅ Updated existing variant ID: {$variant_id}");
                }
            } else {
                // Tạo mới
                $new_variant_data = [
                    'product_id' => $product_id,
                    'sku' => $combo['sku'],
                    'variant_name' => $combo['name'],
                    'variant_signature' => $variant_signature,
                    'price' => $combo['price'] ?? 0,
                    'cost_price' => $combo['cost_price'] ?? 0,
                    'stock_quantity' => 0,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted_at' => NULL
                ];
    
                $this->db->insert('product_variants_v2', $new_variant_data);
                $variant_id = $this->db->insert_id();
    
                log_message('info', "✅ Created new variant ID: {$variant_id}");
            }
    
            // Luôn luôn cập nhật lại attribute values cho variant đó
            $this->save_variant_attributes($product_id, $variant_id, $option_ids);
        }
    
        // Soft-delete orphan variants không còn thuộc group tổ hợp attribute hiện tại
        $this->soft_delete_orphan_variants($product_id, $attribute_combinations);
    
        // Update cờ has_variants
        $this->db->where('id', $product_id)->update('products', ['has_variants' => 1]);
    
        $this->db->trans_complete();
        return $this->db->trans_status();
    }    

    /**
     * Build variant signature từ option_id (mỗi phần tử là option_id)
     * @param array $option_ids - [1, 5, 8]
     * @return string - "1-5-8"
     */
    private function build_variant_signature($option_ids) {
        if (empty($option_ids) || !is_array($option_ids)) {
            return '';
        }
        sort($option_ids);
        return implode('-', $option_ids);
    }    

    /**
     * Soft-delete variants không còn trong combo mới
     * @param int $product_id
     * @param array $new_combinations
     * @return void
     */
    private function soft_delete_orphan_variants($product_id, $new_combinations) {
        // Get new signatures
        $new_signatures = [];
        foreach ($new_combinations as $combo) {
            $new_signatures[] = $this->build_variant_signature($combo['attribute_value_ids']);
        }
        
        // Soft-delete variants NOT in new list AND active
        $this->db
            ->where('product_id', $product_id)
            ->where('deleted_at IS NULL', null, false)
            ->where_not_in('variant_signature', $new_signatures)
            ->update('product_variants_v2', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'status' => 'inactive'
            ]);
    }

    /**
     * Save attribute values cho variant
     * @param int $variant_id
     * @param array $option_ids - [1, 5, 8]
     * @return void
     */
    private function save_variant_attributes($product_id, $variant_id, $option_ids) {
        if (empty($variant_id) || !is_numeric($variant_id) || $variant_id <= 0) {
            log_message('error', '[ATTRIBUTES] save_variant_attributes called with invalid variant_id='.print_r($variant_id, true));
            return false;
        }
    
        foreach ($option_ids as $option_id) {
            $option = $this->db->where('id', $option_id)->get('product_attribute_options')->row_array();
            if (!$option) continue;
    
            // Xóa cặp product_id + attribute_id + option_id nếu có (CỰC KÌ QUAN TRỌNG)
            $this->db->where([
                'product_id' => $product_id,
                'attribute_id' => $option['attribute_id'],
                'option_id' => $option_id
            ])->delete('product_attribute_values');
    
            // Insert mới
            $this->db->insert('product_attribute_values', [
                'product_id'   => $product_id,
                'variant_id'   => $variant_id,
                'attribute_id' => $option['attribute_id'],
                'option_id'    => $option_id,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }
        return true;
    }                
}