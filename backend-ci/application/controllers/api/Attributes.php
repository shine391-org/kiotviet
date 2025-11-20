<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attributes Controller
 * ✅ FIXED: Complete API with proper field handling
 */
class Attributes extends CI_Controller {
    
    protected $user_data;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Product_attribute_model');
        $this->load->library('JwtAuth');
        
        $user = $this->jwtauth->validateToken();
        if (!$user) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
            exit;
        }        
        $this->user_data = $user;
    }
    
    /**
     * GET /api/attributes
     * ✅ FIXED: Return complete attribute list
     * Trả về danh sách thuộc tính kèm số lượng usage_count.
     */
    public function index_get() {
        $search = $this->input->get('search');
        $attrList = $this->Product_attribute_model->get_all_attributes_with_usage(['search' => $search]);
        $this->output_json([
            'success' => true,
            'data' => $attrList
        ]);
    }
    
    /**
     * GET /api/attributes/:id
     * ✅ FIXED: Return complete attribute detail with all fields
     */
    public function detail_get($id) {
        $attribute = $this->Product_attribute_model->get_attribute_by_id($id);
        
        if (!$attribute) {
            $this->output_json(['success' => false, 'message' => 'Attribute not found'], 404);
            return;
        }
        
        // ✅ Load options for select/color/image types
        if (in_array($attribute['type'], ['select', 'color', 'image'])) {
            $options = $this->Product_attribute_model->get_options_by_attribute($id);
            $attribute['options'] = $options;
        } else {
            $attribute['options'] = [];
        }
        
        error_log('🔵 GET /api/attributes/' . $id . ': ' . json_encode($attribute));
        
        $this->output_json([
            'success' => true,
            'data' => $attribute
        ]);
    }
    
    /**
     * POST /api/attributes
     * ✅ FIXED: Complete validation
     */
    public function create_post() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (empty($data)) {
            $data = $this->input->post();
        }
        
        // ✅ Validation
        if (empty($data['name'])) {
            $this->output_json(['success' => false, 'message' => 'Name required'], 400);
            return;
        }
        
        if (empty($data['type'])) {
            $data['type'] = 'text'; // Default
        }
        
        // ✅ Cast boolean fields
        $data['is_required'] = isset($data['is_required']) ? (int)$data['is_required'] : 0;
        $data['is_filterable'] = isset($data['is_filterable']) ? (int)$data['is_filterable'] : 1;
        $data['is_visible'] = isset($data['is_visible']) ? (int)$data['is_visible'] : 1;
        
        error_log('🔵 POST /api/attributes: ' . json_encode($data));
        
        $id = $this->Product_attribute_model->create_attribute($data);
        
        if ($id) {
            // ✅ Return created attribute
            $created = $this->Product_attribute_model->get_attribute_by_id($id);
            
            $this->output_json([
                'success' => true,
                'message' => 'Created',
                'data' => $created
            ], 201);
        } else {
            $this->output_json(['success' => false, 'message' => 'Failed to create'], 500);
        }
    }
    
    /**
     * PUT /api/attributes/:id
     * ✅ FIXED: Complete update with validation
     */
    public function update_put($id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        error_log('🔵 PUT /api/attributes/' . $id . ' - Request: ' . json_encode($data));
        
        // ✅ Validate attribute exists
        $existing = $this->Product_attribute_model->get_attribute_by_id($id);
        if (!$existing) {
            $this->output_json(['success' => false, 'message' => 'Attribute not found'], 404);
            return;
        }
        
        // ✅ Cast boolean fields if present
        if (isset($data['is_required'])) {
            $data['is_required'] = (int)$data['is_required'];
        }
        if (isset($data['is_filterable'])) {
            $data['is_filterable'] = (int)$data['is_filterable'];
        }
        if (isset($data['is_visible'])) {
            $data['is_visible'] = (int)$data['is_visible'];
        }
        
        $result = $this->Product_attribute_model->update_attribute($id, $data);
        
        if ($result !== false) {
            // ✅ Return updated attribute
            $updated = $this->Product_attribute_model->get_attribute_by_id($id);
            
            error_log('✅ UPDATE SUCCESS - New data: ' . json_encode($updated));
            
            $this->output_json([
                'success' => true,
                'message' => 'Updated',
                'data' => $updated
            ]);
        } else {
            error_log('❌ UPDATE FAILED');
            $this->output_json(['success' => false, 'message' => 'Failed to update'], 500);
        }
    }

    /**
     * GET /api/attributes/:id/products
     * Lấy danh sách sản phẩm/biến thể đang sử dụng attribute này (bao gồm cả variant bị soft-delete)
     */
    public function products_get($attribute_id) {
        $include_deleted = $this->input->get('include_deleted') === '1'; // Optional flag
        
        $data = $this->Product_attribute_model->get_products_by_attribute($attribute_id, $include_deleted);
        
        $this->output_json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * DELETE /api/attributes/:id
     * Bảo vệ xóa: chỉ xóa khi không còn usage
     */
    public function delete_delete($id) {
        $result = $this->Product_attribute_model->delete_attribute($id);
        if (is_array($result) && !$result['success']) {
            // Trả về lỗi, kèm số lượng liên kết
            $this->output_json([
                'success' => false,
                'message' => $result['message'],
                'usage_count' => $result['usage_count'] ?? 0
            ], 400);
            return;
        }
        $this->output_json(['success' => true, 'message' => 'Deleted']);
    }
    
    // ============================================
    // OPTIONS ENDPOINTS
    // ============================================
    
    /**
     * GET /api/attributes/:id/options
     */
    public function options_get($attribute_id) {
        $options = $this->Product_attribute_model->get_options_by_attribute($attribute_id);
        foreach ($options as &$option) {
            $option['usage_count'] = $this->Product_attribute_model->count_products_using_option($option['id']);
        }
        $this->output_json([
            'success' => true,
            'data' => $options
        ]);
    }
    
    /**
     * POST /api/attributes/:id/options
     * ✅ FIXED: Use option_name field
     */
    public function create_option_post($attribute_id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (empty($data)) {
            $data = $this->input->post();
        }
        
        $data['attribute_id'] = $attribute_id;
        
        // ✅ Validation
        if (empty($data['option_name'])) {
            $this->output_json(['success' => false, 'message' => 'Option name required'], 400);
            return;
        }
        
        // ✅ Remove invalid fields
        unset($data['product_id']);
        
        error_log('🔵 POST /api/attributes/' . $attribute_id . '/options: ' . json_encode($data));
        
        $id = $this->Product_attribute_model->create_option($data);
        
        if ($id) {
            // ✅ Return created option
            $created = $this->Product_attribute_model->get_option_by_id($id);
            
            $this->output_json([
                'success' => true,
                'message' => 'Created',
                'data' => $created
            ], 201);
        } else {
            $this->output_json(['success' => false, 'message' => 'Failed to create'], 500);
        }
    }
    
    /**
     * PUT /api/attributes/options/:id
     */
    public function update_option_put($id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        error_log('🔵 PUT /api/attributes/options/' . $id . ': ' . json_encode($data));
        
        $result = $this->Product_attribute_model->update_option($id, $data);
        
        if ($result) {
            // ✅ Return updated option
            $updated = $this->Product_attribute_model->get_option_by_id($id);
            
            $this->output_json([
                'success' => true,
                'message' => 'Updated',
                'data' => $updated
            ]);
        } else {
            $this->output_json(['success' => false, 'message' => 'Failed to update'], 500);
        }
    }

    /**
     * GET /api/attributes/options/:id/products
     * Lấy danh sách sản phẩm/biến thể đang sử dụng option này
     */
    public function products_by_option_get($option_id) {
        $include_deleted = $this->input->get('include_deleted') === '1';
        
        $data = $this->Product_attribute_model->get_products_by_option($option_id, $include_deleted);
        
        $this->output_json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * DELETE /api/attributes/options/:id
     * Bảo vệ xóa: chỉ xóa khi không còn usage
     */
    public function delete_option_delete($id) {
        $result = $this->Product_attribute_model->delete_option($id);
        if (is_array($result) && !$result['success']) {
            // Trả về lỗi, kèm số lượng liên kết
            $this->output_json([
                'success' => false,
                'message' => $result['message'],
                'usage_count' => $result['usage_count'] ?? 0
            ], 400);
            return;
        }
        $this->output_json(['success' => true, 'message' => 'Deleted']);
    }
    
    // ============================================
    // VALUES ENDPOINTS (Product/Variant Attributes)
    // ============================================
    
    /**
     * GET /api/products/:id/attribute-values
     * ✅ NEW: Get attribute values assigned to product
     */
    public function product_values_get($product_id) {
        $values = $this->Product_attribute_model->get_values_by_product($product_id);
        
        $this->output_json([
            'success' => true,
            'data' => $values
        ]);
    }

    /**
     * POST /api/products/:id/attribute-values
     * ✅ NEW: Update product attributes (match frontend API call)
     */
    public function update_product_values_post($product_id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // ✅ Accept both 'attribute_values' (from frontend) and 'values' (fallback)
        $values = $data['attribute_values'] ?? $data['values'] ?? [];
        
        if (!is_array($values)) {
            error_log('❌ UPDATE PRODUCT VALUES ERROR: Invalid data - ' . json_encode($data));
            $this->output_json([
                'success' => false, 
                'message' => 'Dữ liệu attribute_values không hợp lệ'
            ], 400);
            return;
        }
        
        error_log('🔵 POST /api/products/' . $product_id . '/attribute-values: ' . json_encode($values));
        
        // ✅ Verify product exists
        $this->load->model('Product_model');
        $product = $this->Product_model->get_by_id_full($product_id);
        
        if (!$product) {
            error_log('❌ PRODUCT NOT FOUND: ' . $product_id);
            $this->output_json([
                'success' => false, 
                'message' => 'Sản phẩm không tồn tại'
            ], 404);
            return;
        }
        
        // ✅ Call model method
        $result = $this->Product_attribute_model->sync_product_values($product_id, $values);
        
        if ($result) {
            // ✅ Return updated values
            $updated = $this->Product_attribute_model->get_values_by_product($product_id);
            
            error_log('✅ UPDATE SUCCESS - Count: ' . count($updated));
            
            $this->output_json([
                'success' => true,
                'message' => 'Cập nhật thuộc tính sản phẩm thành công',
                'data' => $updated
            ]);
        } else {
            error_log('❌ UPDATE FAILED for product ' . $product_id);
            $this->output_json([
                'success' => false, 
                'message' => 'Lỗi cập nhật thuộc tính'
            ], 500);
        }
    }
    
    /**
     * POST /api/products/:id/attribute-values/sync
     * ✅ NEW: Sync attribute values for simple product
     */
    public function sync_product_values_post($product_id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (empty($data['values']) || !is_array($data['values'])) {
            $this->output_json(['success' => false, 'message' => 'Invalid values data'], 400);
            return;
        }
        
        error_log('🔵 POST /api/products/' . $product_id . '/attribute-values/sync: ' . json_encode($data));
        
        $result = $this->Product_attribute_model->sync_product_values($product_id, $data['values']);
        
        if ($result) {
            $this->output_json(['success' => true, 'message' => 'Synced']);
        } else {
            $this->output_json(['success' => false, 'message' => 'Failed to sync'], 500);
        }
    }
    
    /**
     * GET /api/variants/:id/attribute-values
     */
    public function variant_values_get($variant_id) {
        $values = $this->Product_attribute_model->get_values_by_variant($variant_id);
        
        $this->output_json([
            'success' => true,
            'data' => $values
        ]);
    }
    
    /**
     * POST /api/variants/:id/attribute-values/sync
     * ✅ FIX: Match frontend key name
     */
    public function sync_variant_values_post($variant_id) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // ✅ FIX: Accept both 'attribute_values' (frontend) and 'values' (old)
        $values = $data['attribute_values'] ?? $data['values'] ?? null;
        
        if (empty($values) || !is_array($values)) {
            error_log('❌ SYNC ERROR: Invalid data - ' . json_encode($data));
            $this->output_json(['success' => false, 'message' => 'Invalid attribute values data'], 400);
            return;
        }
        
        // ✅ Get product_id from variant
        $this->load->model('product_variant_model');
        $variant = $this->product_variant_model->get_by_id($variant_id);
        
        if (!$variant) {
            error_log('❌ VARIANT NOT FOUND: ' . $variant_id);
            $this->output_json(['success' => false, 'message' => 'Variant not found'], 404);
            return;
        }
        
        $product_id = $variant['product_id'];
        
        error_log('🔵 POST /api/variants/' . $variant_id . '/attribute-values/sync: ' . json_encode($values));
        
        $result = $this->Product_attribute_model->sync_variant_values(
            $variant_id,
            $product_id,
            $values
        );
        
        if ($result) {
            // ✅ Return updated values
            $updated = $this->Product_attribute_model->get_values_by_variant($variant_id);
            
            $this->output_json([
                'success' => true,
                'message' => 'Synced',
                'data' => $updated
            ]);
        } else {
            error_log('❌ SYNC FAILED for variant ' . $variant_id);
            $this->output_json(['success' => false, 'message' => 'Failed to sync'], 500);
        }
    }

    public function remove_attribute_from_variant_delete($variant_id, $attribute_id) {
        $result = $this->Product_attribute_model->remove_attribute_from_variant($variant_id, $attribute_id);
        if ($result) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode(['status' => 'success']));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['status' => 'error']));
        }
    }
    /**
     * POST /api/attributes/:id/generate-variants
     * ✅ NEW LOGIC: Generate hoặc restore variants
     */
    public function generate_variants_post($product_id) {
        $user = $this->jwtauth->validateToken();
        if (!$user) {
            $this->output_json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $this->load->model('Product_model');

        // Check product exists
        $product = $this->Product_model->get_by_id_full($product_id);
        if (!$product) {
            $this->output_json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
            return;
        }

        // Get attribute values
        $attribute_values = $this->Product_attribute_model->get_values_by_product($product_id);
        if (empty($attribute_values)) {
            $this->output_json(['success' => false, 'message' => 'Sản phẩm chưa có thuộc tính'], 400);
            return;
        }

        // ✅ NEW: Generate combinations
        $combinations = $this->Product_attribute_model->generate_combinations($product_id, $attribute_values);
        
        // ✅ NEW: Call new logic
        $result = $this->Product_attribute_model->generate_or_restore_variants($product_id, $combinations);
        
        if ($result) {
            $this->output_json(['success' => true, 'message' => 'Tạo/khôi phục biến thể thành công'], 200);
        } else {
            $this->output_json(['success' => false, 'message' => 'Tạo biến thể thất bại'], 500);
        }
    }
    
    /**
     * API xóa attribute của product
     * Method: DELETE
     * URL: /products/{product_id}/attribute-values/{attribute_id}
     */
    public function removeAttributeFromProduct($product_id, $attribute_id) {
        $deleted = $this->Product_attribute_model->remove_attribute_from_product($product_id, $attribute_id);

        if ($deleted) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => true]));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot delete attribute values']));
        }
    }
    
    /**
     * Output JSON helper
     */
    private function output_json($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
            ->_display();
        exit;
    }
}