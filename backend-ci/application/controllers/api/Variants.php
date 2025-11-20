<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Variants API Controller
 * 
 * Exactly like Products.php - using CI_Controller + method suffixes
 * 
 * @version 1.0
 * @date 2025-11-01
 */

class Variants extends CI_Controller {
    
    protected $user_data;
    
    public function __construct() {
        parent::__construct();
        
        // Load models
        $this->load->model('Product_model');
        $this->load->model('Product_variant_model');
        $this->load->model('Product_attribute_model');
        
        // Load JWT library
        $this->load->library('JwtAuth');
        
        // Validate token
        $user = $this->jwtauth->validateToken();
        
        if (!$user) {
            $this->output_json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 401);
            exit;
        }
        
        $this->user_data = $user;
    }
    
    /**
     * Get variants for product
     * GET /api/products/:product_id/variants
     */
    public function index_get($product_id) {
        try {
            if (empty($product_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID sản phẩm không hợp lệ'
                ], 400);
                return;
            }
            
            // Check if product exists
            $product = $this->Product_model->get_by_id($product_id);
            if (!$product) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
                return;
            }
            
            // Get variants
            $variants = $this->Product_variant_model->get_by_product_id($product_id);
            $total_stock = $this->Product_variant_model->sum_stock_by_product($product_id);
            
            $this->output_json([
                'success' => true,
                'message' => 'Lấy danh sách biến thể thành công',
                'data' => [
                    'product' => [
                        'id' => $product['id'],
                        'code' => $product['code'],
                        'name' => $product['name'],
                        'has_variants' => $product['has_variants']
                    ],
                    'variants' => $variants,
                    'variant_count' => count($variants),
                    'total_stock' => $total_stock
                ]
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Variants::index_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách biến thể: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single variant
     * GET /api/variants/:id
     */
    public function detail_get($variant_id) {
        try {
            if (empty($variant_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID biến thể không hợp lệ'
                ], 400);
                return;
            }
    
            // Gọi method mới có images kèm theo
            $variant = $this->Product_variant_model->get_by_id_with_images($variant_id);
    
            if (!$variant) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy biến thể'
                ], 404);
                return;
            }
    
            $this->output_json([
                'success' => true,
                'message' => 'Lấy thông tin biến thể thành công',
                'data' => $variant
            ]);
        } catch (Exception $e) {
            log_message('error', 'Variants::detail_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin biến thể: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create variant
     * POST /api/products/:product_id/variants
     */
    public function create_post($product_id) {
        try {
            if (empty($product_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID sản phẩm không hợp lệ'
                ], 400);
                return;
            }
    
            $product = $this->Product_model->get_by_id($product_id);
            if (!$product) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
                return;
            }
    
            $raw_data = $this->input->post();
    
            if (empty($raw_data) || !is_array($raw_data)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không có dữ liệu'
                ], 400);
                return;
            }
    
            $data = $raw_data;
    
            // Validate required fields
            if (empty($data['variant_name'])) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Tên biến thể không được để trống'
                ], 400);
                return;
            }
    
            if (empty($data['sku'])) {
                $this->output_json([
                    'success' => false,
                    'message' => 'SKU không được để trống'
                ], 400);
                return;
            }
    
            if ($this->Product_variant_model->check_sku_exists($data['sku'])) {
                $this->output_json([
                    'success' => false,
                    'message' => 'SKU đã tồn tại: ' . $data['sku']
                ], 400);
                return;
            }
    
            if (empty($data['price']) || (float)$data['price'] < 0) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Giá không hợp lệ'
                ], 400);
                return;
            }
    
            $data['product_id'] = $product_id;
            $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['status'] = !empty($data['status']) ? $data['status'] : 'active';
            $data['stock_quantity'] = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
            $data['cost_price'] = isset($data['cost_price']) ? (float)$data['cost_price'] : 0;
            $data['price'] = (float)$data['price'];
            $data['min_stock'] = isset($data['min_stock']) ? (int)$data['min_stock'] : 0;
            $data['max_stock'] = isset($data['max_stock']) ? (int)$data['max_stock'] : 0;
            $data['created_by'] = $this->user_data['id'];
            $data['created_at'] = date('Y-m-d H:i:s');
    
            // Safe check for attributes
            if (isset($data['attributes'])) {
                if (is_string($data['attributes'])) {
                    $attrs = json_decode($data['attributes'], true);
                    if (!is_array($attrs)) {
                        $this->output_json([
                            'success' => false,
                            'message' => 'Attributes không hợp lệ (phải là JSON)'
                        ], 400);
                        return;
                    }
                    $data['attributes'] = json_encode($attrs, JSON_UNESCAPED_UNICODE);
                } elseif (is_array($data['attributes'])) {
                    $data['attributes'] = json_encode($data['attributes'], JSON_UNESCAPED_UNICODE);
                }
            }
    
            unset($data['id'], $data['updated_at'], $data['updated_by'], $data['deleted_at']);
            try {
                $attribute_option_ids = $data['attribute_option_ids'] ?? [];
                $variant_signature = $this->Product_variant_model->build_variant_signature($attribute_option_ids);
                
                if ($this->Product_variant_model->check_variant_signature_exists($product_id, null, $variant_signature)) {
                    $this->output_json(['success' => false, 'message' => 'Biến thể với bộ thuộc tính này đã tồn tại'], 409);
                    return;
                }
                $data['variant_signature'] = $variant_signature;

                $variant_id = $this->Product_variant_model->create($data);
    
                if ($variant_id) {
                    $this->Product_model->update($product_id, ['has_variants' => 1]);
    
                    $variant = $this->Product_variant_model->get_by_id($variant_id);
    
                    $this->output_json([
                        'success' => true,
                        'message' => 'Tạo biến thể thành công',
                        'data' => $variant
                    ], 201);
                } else {
                    $this->output_json([
                        'success' => false,
                        'message' => 'Tạo biến thể thất bại'
                    ], 500);
                }
            } catch (Exception $db_exception) {
                log_message('error', 'Variants::create_post - DB Exception: ' . $db_exception->getMessage());
    
                if (strpos($db_exception->getMessage(), 'Duplicate entry') !== false) {
                    $this->output_json([
                        'success' => false,
                        'message' => 'SKU bị trùng lặp'
                    ], 409);
                } else {
                    $this->output_json([
                        'success' => false,
                        'message' => 'Lỗi database: ' . $db_exception->getMessage()
                    ], 500);
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Variants::create_post - Exception: ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi tạo biến thể: ' . $e->getMessage()
            ], 500);
        }
    }    
    
   /**
     * Update variant - CORRECT FIX
     */
    public function update_put($variant_id) {
        try {
            if (empty($variant_id)) {
                $this->output_json(['success' => false, 'message' => 'ID invalid'], 400);
                return;
            }
    
            $variant = $this->Product_variant_model->get_by_id($variant_id);
            if (!$variant) {
                $this->output_json(['success' => false, 'message' => 'Variant not found'], 404);
                return;
            }
    
            $input = file_get_contents("php://input");
            $data = [];
    
            $content_type = $this->input->server('CONTENT_TYPE') ?? '';
    
            if (strpos($content_type, 'application/json') !== false) {
                $data = json_decode($input, true) ?? [];
            } else {
                parse_str($input, $data);
            }
    
            log_message('info', 'PUT data received: ' . json_encode($data));
    
            if (empty($data)) {
                $this->output_json(['success' => false, 'message' => 'No data provided'], 400);
                return;
            }
    
            if (isset($data['price'])) {
                $data['price'] = (float)$data['price'];
                if ($data['price'] < 0) {
                    $this->output_json(['success' => false, 'message' => 'Price invalid'], 400);
                    return;
                }
            }
    
            if (isset($data['stock_quantity'])) {
                $data['stock_quantity'] = (int)$data['stock_quantity'];
                if ($data['stock_quantity'] < 0) {
                    $this->output_json(['success' => false, 'message' => 'Stock invalid'], 400);
                    return;
                }
            }
    
            // Thêm min_stock và max_stock nếu có
            if (isset($data['min_stock'])) {
                $data['min_stock'] = (int)$data['min_stock'];
            }
            if (isset($data['max_stock'])) {
                $data['max_stock'] = (int)$data['max_stock'];
            }
    
            $data['updated_at'] = date('Y-m-d H:i:s');

            $attribute_option_ids = $data['attribute_option_ids'] ?? [];
            $variant_signature = $this->Product_variant_model->build_variant_signature($attribute_option_ids);
            $variant = $this->Product_variant_model->get_by_id($variant_id);
            $product_id = $variant['product_id'];
            if ($this->Product_variant_model->check_variant_signature_exists($product_id, $variant_id, $variant_signature)) {
                $this->output_json(['success' => false, 'message' => 'Biến thể với bộ thuộc tính này đã tồn tại'], 409);
                return;
            }
            $data['variant_signature'] = $variant_signature;

            $result = $this->Product_variant_model->update($variant_id, $data);
    
            if ($result) {
                $variant = $this->Product_variant_model->get_by_id($variant_id);
                $this->output_json(['success' => true, 'message' => 'Updated', 'data' => $variant]);
            } else {
                $this->output_json(['success' => false, 'message' => 'Update failed'], 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Update error: ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
   
    /**
     * Delete variant
     * DELETE /api/variants/:id
     */
    public function delete_delete($variant_id) {
        try {
            if (empty($variant_id)) {
                $this->output_json(['success' => false, 'message' => 'ID biến thể không hợp lệ'], 400);
                return;
            }
            
            // Check if variant exists
            $variant = $this->Product_variant_model->get_by_id($variant_id);
            if (!$variant) {
                $this->output_json(['success' => false, 'message' => 'Không tìm thấy biến thể'], 404);
                return;
            }
            
            // ✅ NEW: Check if stock > 0
            $stock = isset($variant['stock_quantity']) ? (int)$variant['stock_quantity'] : 0;
            if ($stock > 0) {
                $this->output_json([
                    'success' => false,
                    'message' => "❌ Không thể xóa! Biến thể còn tồn kho ({$stock} cái). Vui lòng xuất bán hết trước."
                ], 400);
                return;
            }
            
            $product_id = $variant['product_id'];
            
            // Delete variant (soft delete)
            $result = $this->Product_variant_model->delete($variant_id);
            
            if ($result) {
                // Check if product still has active variants
                $remaining = $this->Product_variant_model->count_active_by_product($product_id);
                
                // If no variants left, update has_variants flag
                if ($remaining == 0) {
                    $this->Product_model->update($product_id, ['has_variants' => 0]);
                }
                
                $this->output_json(['success' => true, 'message' => 'Xóa biến thể thành công']);
            } else {
                $this->output_json(['success' => false, 'message' => 'Xóa biến thể thất bại'], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Variants::delete_delete - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi xóa biến thể: ' . $e->getMessage()
            ], 500);
        }
    }

    public function upload_multiple_post($variant_id) {
        try {
            $variant_id = (int)$variant_id;
            if ($variant_id <= 0) {
                $this->output_json(['success' => false, 'message' => 'ID biến thể không hợp lệ'], 400);
                return;
            }
    
            $variant = $this->Product_variant_model->get_by_id($variant_id);
            if (!$variant) {
                $this->output_json(['success' => false, 'message' => 'Biến thể không tồn tại'], 404);
                return;
            }
    
            if (empty($_FILES['files'])) {
                $this->output_json(['success' => false, 'message' => 'Vui lòng chọn ít nhất một file ảnh hợp lệ'], 400);
                return;
            }
    
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
    
            $max_size = 5 * 1024 * 1024; // 5MB
            $upload_dir = APPPATH . '../uploads/products/'; // Dùng chung thư mục upload sản phẩm
    
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
    
            $this->load->library('upload');
    
            $uploaded_images = [];
            $failed_uploads = [];
            $file_count = count($_FILES['files']['name']);
    
            for ($i = 0; $i < $file_count; $i++) {
                $_FILES['file']['name'] = $_FILES['files']['name'][$i];
                $_FILES['file']['type'] = $_FILES['files']['type'][$i];
                $_FILES['file']['tmp_name'] = $_FILES['files']['tmp_name'][$i];
                $_FILES['file']['error'] = $_FILES['files']['error'][$i];
                $_FILES['file']['size'] = $_FILES['files']['size'][$i];
            
                $mime_type = mime_content_type($_FILES['file']['tmp_name']);
                if (!isset($allowed_mimes[$mime_type]) || $mime_type !== $_FILES['file']['type']) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => 'Unsupported file type hoặc không khớp MIME',
                    ];
                    continue;
                }
            
                if ($_FILES['file']['size'] < 50 * 1024 || $_FILES['file']['size'] > $max_size) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => 'Kích thước file phải từ 50KB đến 5MB',
                    ];
                    continue;
                }
            
                $ext = $allowed_mimes[$mime_type];
                // Đảm bảo tên file luôn khác biệt (thêm uniqid hoặc timestamp)
                $filename = $variant['sku'] . '_' . uniqid() . '.' . $ext;
                $filepath = $upload_dir . $filename;
            
                $this->upload->initialize([
                    'upload_path' => $upload_dir,
                    'allowed_types' => 'jpg|jpeg|png|gif|webp',
                    'max_size' => 5120,
                    'file_name' => $filename,
                    'overwrite' => false,
                ]);
            
                if (!$this->upload->do_upload('file')) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => $this->upload->display_errors('', ''),
                    ];
                    continue;
                }
            
                $image_url = base_url('uploads/products/' . $filename);
            
                // Lưu vào bảng product_images
                $image_data = [
                    'product_id' => $variant['product_id'],
                    'variant_id' => $variant_id,
                    'image_url' => $image_url,
                    'image_path' => '/uploads/products/' . $filename,
                    'file_name' => $filename,
                    'is_primary' => count($uploaded_images) === 0 ? 1 : 0,
                    'sort_order' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted_at' => null,
                ];
            
                $image_id = $this->Product_model->create_product_image($image_data);
            
                if ($image_id) {
                    $uploaded_images[] = array_merge($image_data, ['id' => $image_id]);
                } else {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => 'Lỗi lưu ảnh vào database',
                    ];
                }
            }            
    
            // Đồng bộ ảnh sản phẩm nếu cần
            if (count($uploaded_images) > 0) {
                $this->Product_model->sync_product_images($variant['product_id']);
            }
    
            $this->output_json([
                'success' => count($uploaded_images) > 0,
                'message' => sprintf('Đã tải lên %d/%d ảnh thành công', count($uploaded_images), $file_count),
                'data' => $uploaded_images,
                'uploaded_count' => count($uploaded_images),
                'failed_count' => count($failed_uploads),
                'errors' => $failed_uploads,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Variants::upload_multiple_post - ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }
    
    public function attach_multiple_images($variantId) {
        $input = file_get_contents('php://input');
        $postData = json_decode($input, true);
    
        if (!isset($postData['image_ids'])) {
            $this->output_json(['success' => false, 'message' => 'Danh sách ảnh rỗng hoặc không hợp lệ'], 400);
            return;
        }
        $imageIds = $postData['image_ids'];
    
        if (empty($variantId) || !is_numeric($variantId)) {
            $this->output_json(['success' => false, 'message' => 'Variant ID không hợp lệ'], 400);
            return;
        }
        if (empty($imageIds) || !is_array($imageIds)) {
            $this->output_json(['success' => false, 'message' => 'Danh sách ảnh rỗng hoặc không hợp lệ'], 400);
            return;
        }
    
        try {
            $result = $this->Product_variant_model->attach_images($variantId, $imageIds);
            // Phải kiểm tra đúng success, KHÔNG lấy truthy của array!
            if (!is_array($result) || !isset($result['success']) || !$result['success']) {
                $msg = is_array($result) && isset($result['message']) ? $result['message'] : 'Gắn ảnh thất bại';
                $this->output_json(['success' => false, 'message' => $msg], 500);
                return;
            }
        } catch (Exception $ex) {
            log_message('error', 'attach_images exception: ' . $ex->getMessage());
            $this->output_json(['success' => false, 'message' => 'Lỗi khi gắn ảnh'], 500);
            return;
        }
    
        // Trả về data ảnh mới sau khi attach
        $variant = $this->Product_variant_model->get_by_id_with_images($variantId);
    
        $this->output_json(array_merge(
            $result,
            [
                'data' => $variant['images'] ?? [],
            ]
        ));
    }
    
    /**
     * GET /api/variants/:variant_id/attributes
     * Lấy danh sách attributes và giá trị option đã gán cho biến thể
     */
    public function attributes_get($variant_id) {
        try {
            if (empty($variant_id) || !is_numeric($variant_id)) {
                $this->output_json(['success' => false, 'message' => 'ID biến thể không hợp lệ'], 400);
                return;
            }

            // Lấy product_id của variant để lấy các attribute áp dụng
            $variant = $this->Product_variant_model->get_by_id($variant_id);
            if (!$variant) {
                $this->output_json(['success' => false, 'message' => 'Không tìm thấy biến thể'], 404);
                return;
            }
            $product_id = $variant['product_id'];

            // Lấy danh sách attributes áp dụng cho product này
            $attributes = $this->Product_attribute_model->get_by_product($product_id);

            // Lấy danh sách giá trị option đã gán cho biến thể này (attribute values)
            $attribute_values = $this->Product_attribute_model->get_values_by_variant($variant_id);

            $this->output_json([
                'success' => true,
                'message' => 'Lấy danh sách attributes thành công',
                'data' => [
                    'attributes' => $attributes,
                    'values' => $attribute_values,
                ],
            ]);
        } catch (Exception $e) {
            log_message('error', 'Variants::attributes_get - ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }


    /**
     * POST /api/variants/:variant_id/attribute-values/sync
     * Đồng bộ (tạo/cập nhật/xóa) attribute-values của biến thể
     * Data: [{ attribute_id, option_id }, ...]
     */
    public function attribute_values_sync_post($variant_id) {
        try {
            if (empty($variant_id) || !is_numeric($variant_id)) {
                $this->output_json(['success' => false, 'message' => 'ID biến thể không hợp lệ'], 400);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $this->output_json(['success' => false, 'message' => 'Dữ liệu không đúng định dạng'], 400);
                return;
            }

            // Xóa các giá trị cũ
            $this->Product_attribute_model->delete_values_by_variant($variant_id);

            // Chèn các giá trị mới
            $data_to_insert = [];
            foreach ($input as $item) {
                if (!empty($item['attribute_id']) && !empty($item['option_id'])) {
                    $data_to_insert[] = [
                        'variant_id' => $variant_id,
                        'attribute_id' => (int)$item['attribute_id'],
                        'option_id' => (int)$item['option_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }

            if (!empty($data_to_insert)) {
                $this->Product_attribute_model->insert_values_batch($data_to_insert);
            }

            $this->output_json([
                'success' => true,
                'message' => 'Đồng bộ thuộc tính biến thể thành công',
            ]);
        } catch (Exception $e) {
            log_message('error', 'Variants::attribute_values_sync_post - ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Restore variant (khôi phục biến thể đã xóa mềm)
     * PUT /api/variants/:variant_id/restore
     */
    public function restore_put($variant_id) {
        try {
            if (empty($variant_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID biến thể không hợp lệ'
                ], 400);
                return;
            }
            
            // Kiểm tra variant đã bị xóa mềm (status inactive, deleted_at không null)
            $variant = $this->Product_variant_model->get_deleted_variant($variant_id);
            if (!$variant) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy biến thể đã xóa'
                ], 404);
                return;
            }
            
            // Gọi model restore variant + attribute values
            $success = $this->Product_variant_model->restore_with_attributes($variant_id);
            
            if ($success) {
                // Cập nhật flag has_variants cho product nếu cần
                $this->Product_model->update($variant['product_id'], ['has_variants' => 1]);
                
                // Trả về variant vừa restore
                $restored_variant = $this->Product_variant_model->get_by_id($variant_id);
                $this->output_json([
                    'success' => true,
                    'message' => 'Khôi phục biến thể thành công',
                    'data' => $restored_variant
                ]);
            } else {
                $this->output_json([
                    'success' => false,
                    'message' => 'Khôi phục biến thể thất bại'
                ], 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Variants::restore_put - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi khôi phục biến thể: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách biến thể đã xóa mềm theo product_id
     * GET /api/variants/deleted?product_id=123
     */
    public function deleted_get() {
        try {
            $product_id = $this->input->get('product_id');
            if (empty($product_id) || !is_numeric($product_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'product_id không hợp lệ'
                ], 400);
                return;
            }

            $deleted_variants = $this->Product_variant_model->get_deleted_variants($product_id);

            $this->output_json([
                'success' => true,
                'message' => 'Lấy danh sách biến thể đã xóa thành công',
                'data' => $deleted_variants
            ]);
        } catch (Exception $e) {
            log_message('error', 'Variants::deleted_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hard delete variant (xóa vĩnh viễn - không thể khôi phục)
     * DELETE /api/variants/:variant_id/hard
     */
    public function hard_delete_delete($variant_id) {
        try {
            if (empty($variant_id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID biến thể không hợp lệ'
                ], 400);
                return;
            }

            // Gọi model để xóa
            $result = $this->Product_variant_model->hard_delete_variant($variant_id);

            if ($result['success']) {
                // Nếu không còn variant nào active, cập nhật cờ has_variants trên product
                $remaining = $this->Product_variant_model->count_active_by_product($result['product_id']);
                if ($remaining == 0) {
                    $this->Product_model->update($result['product_id'], ['has_variants' => 0]);
                }

                $this->output_json([
                    'success' => true,
                    'message' => '✅ Đã xóa vĩnh viễn biến thể'
                ]);
            } else {
                $this->output_json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Xóa vĩnh viễn thất bại'
                ], 400);
            }

        } catch (Exception $e) {
            log_message('error', 'Variants::hard_delete_delete - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi xóa vĩnh viễn biến thể: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Output JSON response - SAME AS Products.php
     */
    private function output_json($data, $status_code = 200) {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
            ->_display();
        exit;
    }
}
?>