<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Products API Controller
 * 
 * Features:
 * - Full CRUD operations with validation
 * - Auto-generate unique product code
 * - Duplicate code prevention
 * - Exception handling
 * - JWT authentication
 * - Comprehensive error messages
 * 
 * @version 2.0
 * @date 2025-10-27
 */

class Products extends CI_Controller {
    
    protected $user_data;
    
    public function __construct() {
        parent::__construct();
        
        // Load models
        $this->load->model('Product_model');
        $this->load->model('Product_attribute_model');
        
        // Load JWT library
        $this->load->library('JwtAuth');
        
        // ✅ FIXED: Validate token using correct method
        $user = $this->jwtauth->validateToken();
        
        if (!$user) {
            $this->output_json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 401);
            exit;
        }
        
        // Store user data for use in other methods
        $this->user_data = $user;
    }

    /**
     * Get products list with pagination and filters
     * GET /api/products?include_variants=true
     * 
     * ✨ UPDATED: Get DB total BEFORE limit/offset
     */
    public function index_get() {
        try {
            $search = $this->input->get('search', TRUE);
            $category_id = $this->input->get('category_id', TRUE);
            $product_type = $this->input->get('product_type', TRUE);
            $status = $this->input->get('status', TRUE);
            $is_active = $this->input->get('is_active', TRUE);
            $brand = $this->input->get('brand', TRUE);
            $include_variants = $this->input->get('include_variants', TRUE);
            $page = max(1, (int)$this->input->get('page', TRUE));
            $limit = max(1, (int)$this->input->get('limit', TRUE));
            $sort_by = $this->input->get('sort_by', TRUE) ?: 'p.id';
            $order_raw = $this->input->get('order');
            $order = $order_raw ? strtoupper($order_raw) : 'DESC';
            $offset = ($page - 1) * $limit;
            $stock_status = $this->input->get('stock_status', TRUE);
    
            $attributesInput = $this->input->get('attributes');
            $attributes = [];
            if (is_array($attributesInput)) {
                $attributes = $attributesInput;
            } elseif (is_string($attributesInput) && $attributesInput !== '') {
                $attributes = json_decode($attributesInput, true);
            }
    
            $result = $this->Product_model->get_products(
                $search,
                $category_id,
                $product_type,
                $status,
                $is_active,
                $brand,
                $limit,
                $offset,
                $sort_by,
                $order,
                $stock_status,
                $attributes
            );
    
            $total_count = $result['total'];
            $products = $result['data'];
    
            $filtered_products = [];
            foreach ($products as $product) {
                if (empty($product['parent_product_id'])) {
                    $filtered_products[] = $product;
                }
            }
    
            if ($include_variants === 'true' || $include_variants === true || $include_variants === 1) {
                $this->load->model('Product_variant_model');
                foreach ($filtered_products as &$product) {
                    $this->Product_variant_model->db->reset_query();
                    $variants = $this->Product_variant_model->get_by_product_id($product['id']);
                    $product['has_variants'] = !empty($variants) ? 1 : 0;
                    $product['variants'] = $variants;
                    $product['variant_count'] = count($variants);
                    $product['total_variant_stock'] = $product['total_stock'] ?? 0;
                    $product['stock_quantity'] = $product['total_variant_stock'];
                }
            }
    
            $total_pages = ceil($total_count / $limit);
    
            // ✅ Dữ liệu product mỗi hàng đã có category_ids/category_names mảng nhiều-nhiều phục vụ frontend
            $this->output_json([
                'success' => true,
                'message' => 'Danh sách sản phẩm lấy thành công.',
                'data' => $filtered_products,
                'pagination' => [
                    'total' => (int)$total_count,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total_pages' => (int)$total_pages
                ]
            ]);
        } catch (Exception $e) {
            log_message('error', 'Products::index_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }                                                
    
    /**
     * Create new product
     * POST /api/products
     */
    public function create_post() {
        try {
            $raw_data = json_decode(file_get_contents("php://input"), true);
            if (!$raw_data) {
                $raw_data = $this->input->post();
            }
    
            if (empty($raw_data) || !is_array($raw_data)) {
                return $this->output_json([
                    'success' => false,
                    'message' => 'Không có dữ liệu'
                ], 400);
            }
    
            $data = $raw_data;
    
            // Validate ít nhất 1 category
            if (
                !isset($data['category_id']) ||
                (is_array($data['category_id']) && count($data['category_id']) === 0) ||
                (!is_array($data['category_id']) && empty($data['category_id']))
            ) {
                return $this->output_json([
                    'success' => false,
                    'message' => 'Phải chọn ít nhất 1 danh mục'
                ], 400);
            }
    
            // Các validate, sinh code, sinh slug, barcode... giữ y nguyên
            if (empty($data['name'])) {
                return $this->output_json([
                    'success' => false,
                    'message' => 'Tên sản phẩm không được để trống'
                ], 400);
            }
    
            // Generate or check code
            if (empty($data['code'])) {
                $max_attempts = 10;
                $attempt = 0;
                do {
                    $data['code'] = $this->Product_model->get_next_code('SP');
                    $code_exists = $this->Product_model->check_code_exists($data['code']);
                    $attempt++;
                    if ($attempt >= $max_attempts) {
                        return $this->output_json([
                            'success' => false,
                            'message' => 'Không thể tạo mã sản phẩm duy nhất sau ' . $max_attempts . ' lần thử'
                        ], 500);
                    }
                } while ($code_exists);
            } else {
                if ($this->Product_model->check_code_exists($data['code'])) {
                    return $this->output_json([
                        'success' => false,
                        'message' => 'Mã sản phẩm đã tồn tại: ' . $data['code']
                    ], 400);
                }
            }
    
            // Generate or validate slug
            if (empty($data['slug'])) {
                $base_slug = $this->Product_model->generate_slug($data['name']);
                $data['slug'] = $base_slug;
                $slug_counter = 1;
                while ($this->Product_model->check_slug_exists($data['slug'])) {
                    $data['slug'] = $base_slug . '-' . $slug_counter;
                    $slug_counter++;
                    if ($slug_counter > 100) {
                        return $this->output_json([
                            'success' => false,
                            'message' => 'Không thể tạo slug duy nhất'
                        ], 500);
                    }
                }
            } else {
                if ($this->Product_model->check_slug_exists($data['slug'])) {
                    $base_slug = $this->Product_model->generate_slug($data['name']);
                    $data['slug'] = $base_slug;
                    $slug_counter = 1;
                    while ($this->Product_model->check_slug_exists($data['slug'])) {
                        $data['slug'] = $base_slug . '-' . $slug_counter;
                        $slug_counter++;
                    }
                }
            }
    
            if (!empty($data['barcode'])) {
                if ($this->Product_model->check_barcode_exists($data['barcode'])) {
                    return $this->output_json([
                        'success' => false,
                        'message' => 'Barcode đã tồn tại: ' . $data['barcode']
                    ], 400);
                }
            }
    
            $data['has_variants'] = !empty($data['has_variants']) ? 1 : 0;
            if ($data['has_variants']) {
                $data['selling_price'] = null;
                $data['purchase_price'] = null;
                $data['stock_quantity'] = null;
            }
    
            $data['is_active'] = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $data['status'] = !empty($data['status']) ? $data['status'] : 'active';
            $data['product_type'] = !empty($data['product_type']) ? $data['product_type'] : 'goods';
            $data['created_by'] = $this->user_data['id'];
            $data['created_at'] = date('Y-m-d H:i:s');
    
            unset($data['id'], $data['updated_at'], $data['updated_by'], $data['deleted_at']);
    
            $optional_fields = ['barcode', 'description', 'image', 'category_id', 
                'purchase_price', 'selling_price', 'wholesale_price', 
                'stock_quantity', 'unit', 'weight', 'dimensions', 'brand'];
            foreach ($optional_fields as $field) {
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }
    
            try {
                $this->db->trans_start();
                $product_id = $this->Product_model->create($data);
                $this->db->trans_complete();
    
                if ($product_id && $this->db->trans_status() !== FALSE) {
                    $product = $this->Product_model->get_by_id($product_id);
                    return $this->output_json([
                        'success' => true,
                        'message' => 'Tạo sản phẩm thành công',
                        'data' => $product
                    ], 201);
                } else {
                    log_message('error', 'Products::create_post - create() returned false');
                    log_message('error', 'Products::create_post - Data: ' . json_encode($data));
                    return $this->output_json([
                        'success' => false,
                        'message' => 'Tạo sản phẩm thất bại'
                    ], 500);
                }
            } catch (Exception $db_exception) {
                log_message('error', 'Products::create_post - DB Exception: ' . $db_exception->getMessage());
                if (strpos($db_exception->getMessage(), 'Duplicate entry') !== false) {
                    return $this->output_json([
                        'success' => false,
                        'message' => 'Mã sản phẩm hoặc slug bị trùng lặp. Vui lòng thử lại.'
                    ], 409);
                }
                return $this->output_json([
                    'success' => false,
                    'message' => 'Lỗi database: ' . $db_exception->getMessage()
                ], 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Products::create_post - Exception: ' . $e->getMessage());
            log_message('error', 'Products::create_post - Trace: ' . $e->getTraceAsString());
            return $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi tạo sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }    

    /**
     * Update product
     * PUT /api/products/:id
     * FIXED: Track changed fields properly, handle has_variants
     */
    public function update($id) {
        try {
            if (empty($id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID sản phẩm không hợp lệ'
                ], 400);
                return;
            }
    
            $old_product = $this->Product_model->get_by_id($id);
            if (!$old_product) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
                return;
            }
    
            $raw_input = file_get_contents("php://input");
            $data = json_decode($raw_input, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
                parse_str($raw_input, $put_data);
                $data = $put_data;
            }
            log_message('error', 'UPDATE DATA: ' . print_r($data, true));
    
            if (!is_array($data) || empty($data)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không có dữ liệu để cập nhật'
                ], 400);
                return;
            }
    
            if (!empty($data['code']) && $data['code'] !== $old_product['code']) {
                if ($this->Product_model->check_code_exists($data['code'], $id)) {
                    $this->output_json([
                        'success' => false,
                        'message' => 'Mã sản phẩm đã tồn tại: ' . $data['code']
                    ], 400);
                    return;
                }
            }
    
            if (!empty($data['name']) && $data['name'] !== $old_product['name']) {
                $data['slug'] = $this->Product_model->generate_slug($data['name'], $id);
            }
    
            if (!empty($data['barcode']) && $data['barcode'] !== $old_product['barcode']) {
                if ($this->Product_model->check_barcode_exists($data['barcode'], $id)) {
                    $this->output_json([
                        'success' => false,
                        'message' => 'Barcode đã tồn tại: ' . $data['barcode']
                    ], 400);
                    return;
                }
            }
    
            foreach (['is_active', 'is_featured', 'is_available_online', 'has_variants'] as $bool_field) {
                if (isset($data[$bool_field])) {
                    if (is_bool($data[$bool_field])) {
                        $data[$bool_field] = $data[$bool_field] ? 1 : 0;
                    } elseif ($data[$bool_field] === 'true' || $data[$bool_field] === true) {
                        $data[$bool_field] = 1;
                    } elseif ($data[$bool_field] === 'false' || $data[$bool_field] === false) {
                        $data[$bool_field] = 0;
                    } else {
                        $data[$bool_field] = (int)$data[$bool_field];
                    }
                    log_message('error', "NORMALIZED: $bool_field = " . $data[$bool_field] . " (type: " . gettype($data[$bool_field]) . ")");
                }
            }
    
            if (!empty($data['has_variants']) && $data['has_variants']) {
                unset($data['selling_price']);
                unset($data['purchase_price']);
                unset($data['stock_quantity']);
            }
    
            $data['updated_by'] = $this->user_data['id'] ?? 1;
            $data['updated_at'] = date('Y-m-d H:i:s');
    
            $protected_fields = ['id', 'created_at', 'created_by', 'deleted_at', 'parent_product_id'];
            foreach ($protected_fields as $field) {
                unset($data[$field]);
            }
    
            $allowed_columns = [
                'product_type', 'code', 'barcode', 'name', 'slug', 'category_id', 'brand',
                'warehouse_location', 'unit', 'base_unit_code', 'unit_conversion',
                'purchase_price', 'selling_price', 'commission_percent', 'commission_amount',
                'wholesale_price', 'stock_quantity', 'alert_stock', 'expiry_days',
                'customer_ordered', 'expected_out_date', 'min_stock_alert', 'max_stock_alert',
                'has_variants', 'related_product_codes', 'image', 'images', 'weight',
                'dimensions', 'location', 'warranty_period', 'maintenance_schedule',
                'description', 'content', 'note_template', 'combo_items',
                'is_active', 'is_available_online', 'is_featured', 'status',
                'meta_title', 'meta_description', 'meta_keywords', 'updated_by', 'updated_at'
            ];
    
            $data = array_intersect_key($data, array_flip($allowed_columns));
    
            $update_result = $this->Product_model->update($id, $data);
    
            if ($update_result) {
                if (is_array($update_result) && isset($update_result['success'])) {
                    $product = $update_result['product'];
                    $changed_fields = $update_result['changed_fields'] ?? [];
                } else {
                    $product = $this->Product_model->get_by_id($id);
                    $changed_fields = [];
                }
                log_message('error', 'CHANGED FIELDS: ' . json_encode($changed_fields));
    
                $this->output_json([
                    'success' => true,
                    'message' => 'Cập nhật sản phẩm thành công',
                    'data' => $product,
                    'updated_fields' => $changed_fields,
                    'updated_count' => count($changed_fields)
                ]);
            } else {
                log_message('error', 'Products::update - update() returned false');
                $this->output_json([
                    'success' => false,
                    'message' => 'Cập nhật sản phẩm thất bại'
                ], 500);
            }
    
        } catch (Exception $e) {
            log_message('error', 'Products::update - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }    

    /**
     * Get product by ID with full details
     * GET /api/products/:id
     */
    public function detail_get($id) {
        try {
            if (empty($id)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'ID sản phẩm không hợp lệ'
                ], 400);
                return;
            }
    
            $product = $this->Product_model->get_by_id_full($id);
    
            if (!$product) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm'
                ], 404);
                return;
            }
    
            // ❌ KHÔNG DÙNG get_category_info(field category_id) nữa
            // Thay vào đó trả về category_ids/category_names/categories nhiều-nhiều cho frontend
            // $product['category_ids'] = array_column($product['categories'], 'id');
            // $product['category_names'] = array_column($product['categories'], 'name');
    
            if (!empty($product['has_variants'])) {
                $this->load->model('Product_variant_model');
                $variants = $this->Product_variant_model->get_by_product_id($id);
                $product['variants'] = $variants;
                $product['variant_count'] = count($variants);
                if (!empty($variants)) {
                    $product['total_variant_stock'] = $this->Product_variant_model->sum_stock_by_product($id);
                }
            }
    
            $this->output_json([
                'success' => true,
                'message' => 'Lấy thông tin sản phẩm thành công',
                'data' => $product
            ]);
    
        } catch (Exception $e) {
            log_message('error', 'Products::detail_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi lấy thông tin sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }            
    
    /**
     * Delete product (soft delete)
     * DELETE /api/products/:id
     */
    public function delete($id) {
        try {
            if (empty($id)) {
                $this->output_json(['success' => false, 'message' => 'ID sản phẩm không hợp lệ'], 400);
                return;
            }
            
            // Check if product exists
            $product = $this->Product_model->get_by_id($id);
            if (!$product) {
                $this->output_json(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
                return;
            }
            
            // ✅ NEW: Check if stock > 0
            $stock = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
            if ($stock > 0) {
                $this->output_json([
                    'success' => false,
                    'message' => "❌ Không thể xóa! Sản phẩm còn tồn kho ({$stock} cái). Vui lòng xuất bán hết trước."
                ], 400);
                return;
            }
            
            // Delete product (soft delete)
            $result = $this->Product_model->delete($id);
            
            if ($result) {
                $this->output_json(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
            } else {
                $this->output_json(['success' => false, 'message' => 'Xóa sản phẩm thất bại'], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Products::delete - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi xóa sản phẩm: ' . $e->getMessage()
            ], 500);
        }
    }
    
    
    /**
     * Get product statistics
     * GET /api/products/statistics
     */
    public function statistics_get() {
        try {
            $stats = $this->Product_model->get_statistics();
            
            $this->output_json([
                'success' => true,
                'message' => 'Lấy thống kê thành công',
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Products::statistics_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Quick search for autocomplete
     * GET /api/products/search
     */
    public function search_get() {
        try {
            $keyword = $this->input->get('q', TRUE);
            $limit = (int)$this->input->get('limit', TRUE) ?: 10;
            
            if (empty($keyword)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Từ khóa tìm kiếm không được để trống'
                ], 400);
                return;
            }
            
            $products = $this->Product_model->quick_search($keyword, $limit);
            
            $this->output_json([
                'success' => true,
                'message' => 'Tìm kiếm thành công',
                'data' => $products
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'Products::search_get - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi khi tìm kiếm: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Output JSON response
     * 
     * @param array $data
     * @param int $status_code
     */
    private function output_json($data, $status_code = 200) {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
            ->_display();
        exit;
    }

    /**
     * Get product images (đúng schema)
     * GET /api/products/:id/images
     */
    public function get_images($product_id) {
        try {
            $images = $this->db
                ->select('id, product_id, image_url, image_path, is_primary, sort_order, created_at, updated_at')
                ->from('product_images')
                ->where('product_id', (int)$product_id)
                ->where('deleted_at', NULL)
                ->order_by('is_primary DESC, sort_order ASC, created_at DESC')
                ->get()
                ->result_array();

            $this->output_json(['success' => true, 'data' => $images]);

        } catch (Exception $e) {
            log_message('error', 'get_images failed: ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Set primary image (đúng logic schema)
     * PUT /api/products/images/:id/set-primary
     */
    public function set_primary_image($image_id) {
        try {
            $image = $this->db->get_where('product_images', ['id' => (int)$image_id])->row_array();
            if (!$image) {
                $this->output_json(['success' => false, 'message' => 'Không tìm ảnh'], 404);
                return;
            }

            $product_id = $image['product_id'];

            // ✅ Reset is_primary for all images of this product
            $this->db->update('product_images', 
                ['is_primary' => 0],
                ['product_id' => $product_id, 'deleted_at' => NULL]
            );

            // ✅ Set this image as primary
            $this->db->update('product_images',
                ['is_primary' => 1, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => (int)$image_id]
            );

            // ✅ Update products.image field with URL
            $this->db->update('products',
                ['image' => $image['image_url']],
                ['id' => $product_id]
            );

            log_message('info', "Set primary: Image=$image_id, Product=$product_id");

            $this->output_json(['success' => true, 'message' => 'Đặt ảnh chính thành công']);

        } catch (Exception $e) {
            log_message('error', 'set_primary_image: ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Log detailed error
     * @param string $method
     * @param Exception $e
     * @param array $context
     */
    private function log_error($method, $e, $context = []) {
        $error_data = [
            'method' => $method,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
            'user_id' => isset($this->user_data['id']) ? $this->user_data['id'] : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        log_message('error', 'Products::' . $method . ' - ' . json_encode($error_data, JSON_UNESCAPED_UNICODE));
    }
        // ========================================================================
    // 🆕 PHASE 1B: MEDIA LIBRARY ENDPOINTS - NEW SECTION
    // ========================================================================

    /**
     * Get media library - browse all product images
     * GET /api/products/media/library
     * 
     * @param int $limit - Items per page (default: 50)
     * @param int $offset - Pagination offset (default: 0)
     * @return JSON
     */
    /**
     * GET /api/products/media/library
     * ✅ Get media library with pagination & filters
     */
    public function get_media_library_get() {
        try {
        // ✅ FIX: Validate and set defaults
        $limit = (int)$this->input->get('limit', TRUE) ?: 12;
        $offset = (int)$this->input->get('offset', TRUE) ?: 0;
        
        // ✅ Ensure valid range
        if ($limit <= 0 || $limit > 100) $limit = 12;
        if ($offset < 0) $offset = 0;
        
        // ✅ FIX: Build filters array
        $filters = [];
        
        if ($this->input->get('product_id')) {
            $filters['product_id'] = (int)$this->input->get('product_id');
        }
        
        if ($this->input->get('brand')) {
            $filters['brand'] = $this->input->get('brand', TRUE);
        }
        
        if ($this->input->get('sku')) {
            $filters['sku'] = $this->input->get('sku', TRUE);
        }
        
        if ($this->input->get('year') && $this->input->get('month')) {
            $filters['year'] = (int)$this->input->get('year');
            $filters['month'] = (int)$this->input->get('month');
        }
        
        // ✅ FIX: Get media library from model
        $result = $this->Product_model->get_media_library($limit, $offset, $filters);
        
        // ✅ FIX: Safe pagination response
        $totalCount = isset($result['total']) && is_numeric($result['total']) ? (int)$result['total'] : 0;
        $pages = $totalCount > 0 ? ceil($totalCount / $limit) : 0;
        
        $this->output_json([
            'success' => true,
            'message' => $result['message'] ?? 'Media library retrieved successfully',
            'data' => $result['data'] ?? [],
            'pagination' => [
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => $pages
            ]
        ]);
        
        } catch (Exception $e) {
        log_message('error', 'get_media_library_get - ' . $e->getMessage());
        $this->output_json([
            'success' => false,
            'message' => 'Error retrieving media library: ' . $e->getMessage(),
            'data' => [],
            'pagination' => [
            'total' => 0,
            'limit' => 12,
            'offset' => 0,
            'pages' => 0
            ]
        ], 500);
        }
    }  


    /**
     * Get media by date (month/year)
     * GET /api/products/media/by-date
     * 
     * @param int $year - 4-digit year (required)
     * @param int $month - 1-12 month (required)
     * @return JSON
     */
    public function get_media_by_date_get() {
        try {
            $year = (int)$this->input->get('year', TRUE);
            $month = (int)$this->input->get('month', TRUE);
            $limit = (int)$this->input->get('limit', TRUE) ?: 50;
            $offset = (int)$this->input->get('offset', TRUE) ?: 0;
            
            if (empty($year) || empty($month)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Year and month are required'
                ], 400);
                return;
            }
            
            if ($month < 1 || $month > 12) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Invalid month (1-12)'
                ], 400);
                return;
            }
            
            // Get media by date
            $result = $this->Product_model->get_media_by_date($year, $month, $limit, $offset);

            // ✅ FIX: Check if model returned error
            if (!$result['success']) {
                $this->output_json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => [],
                    'pagination' => $result['pagination']
                ], 400);
                return;
            }
            
            $this->output_json([
                'success' => true,
                'message' => "Media for {$month}/{$year} retrieved successfully",
                'data' => $result['data'],
                'pagination' => $result['pagination']  // ← Use pagination from model directly
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'get_media_by_date - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Error retrieving media by date: ' . $e->getMessage(),
                'data' => [],
                'pagination' => ['total' => 0, 'limit' => $limit, 'offset' => $offset, 'pages' => 0]
            ], 500);
        }
    }

    /**
     * Get used attribute options by product
     * GET /api/products/:product_id/used-attribute-options
     * 
     * Trả về danh sách option_id đã được dùng trong các variant của product
     * Format: { attribute_id: [option_id1, option_id2, ...], ... }
     */
    public function used_attribute_options_get($product_id) {
        try {
            if (empty($product_id)) {
                $this->output_json(['success' => false, 'message' => 'Product ID invalid'], 400);
                return;
            }
            
            // Load Product_attribute_model
            $this->load->model('Product_attribute_model');
            
            // Lấy tất cả variant của product (bao gồm soft delete)
            $this->db->select('id');
            $this->db->where('product_id', $product_id);
            $variants = $this->db->get('product_variants_v2')->result_array();
            
            if (empty($variants)) {
                $this->output_json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Chưa có variant nào'
                ]);
                return;
            }
            
            $variant_ids = array_column($variants, 'id');
            
            // Lấy tất cả attribute_values của các variant này (bao gồm soft delete)
            $this->db->select('attribute_id, option_id');
            $this->db->where_in('variant_id', $variant_ids);
            $this->db->where('option_id IS NOT NULL', null, false);
            // KHÔNG filter deleted_at => lấy cả soft delete
            $values = $this->db->get('product_attribute_values')->result_array();
            
            // Group theo attribute_id
            $used_map = [];
            foreach ($values as $val) {
                $attr_id = $val['attribute_id'];
                $opt_id = $val['option_id'];
                
                if (!isset($used_map[$attr_id])) {
                    $used_map[$attr_id] = [];
                }
                
                if (!in_array($opt_id, $used_map[$attr_id])) {
                    $used_map[$attr_id][] = (int)$opt_id;
                }
            }
            
            $this->output_json([
                'success' => true,
                'data' => $used_map,
                'message' => 'Lấy danh sách options đã dùng thành công'
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'used_attribute_options_get error: ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Search media by SKU
     * GET /api/products/media/search-sku
     * 
     * @param string $sku - Product code/SKU (required)
     * @return JSON
     */
    public function search_media_by_sku_get() {
        try {
            $sku = $this->input->get('sku', TRUE);
            $limit = (int)$this->input->get('limit', TRUE) ?: 20;
            
            if (empty($sku)) {
                $this->output_json([
                    'success' => false,
                    'message' => 'SKU parameter is required'
                ], 400);
                return;
            }
            
            // Search media
            $images = $this->Product_model->search_media_by_sku($sku, $limit);
            
            $this->output_json([
                'success' => true,
                'message' => 'Search completed',
                'data' => $images,
                'total' => count($images)
            ]);
            
        } catch (Exception $e) {
            log_message('error', 'search_media_by_sku - ' . $e->getMessage());
            $this->output_json([
                'success' => false,
                'message' => 'Error searching media: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Attach multiple images to product
     * POST /api/products/:id/images/attach-multiple
     * 
     * Request body:
     * {
     *   "image_ids": [1, 2, 3]  // Array of product_images.id
     * }
     */
    public function attach_multiple_images($product_id) {
        try {
            $product_id = (int)$product_id;
            
            if ($product_id <= 0) {
                log_message('error', "❌ Invalid product_id: {$product_id}");
                $this->output_json(['success' => false, 'message' => 'Product ID không hợp lệ'], 400);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['image_ids']) || !is_array($input['image_ids'])) {
                log_message('error', "❌ Invalid image_ids: " . json_encode($input));
                $this->output_json(['success' => false, 'message' => 'image_ids is required and must be an array'], 400);
                return;
            }
            
            $image_ids = array_map('intval', $input['image_ids']);
            
            log_message('info', "🔍 CONTROLLER START: product={$product_id}, image_ids=" . json_encode($image_ids));
            
            $result = $this->Product_model->attach_multiple_images($product_id, $image_ids);
            
            log_message('info', "📦 MODEL RESULT: " . json_encode($result));
            
            if (!isset($result['success'])) {
                log_message('error', "❌ Model did not return 'success' field");
                $this->output_json(['success' => false, 'message' => 'Internal error'], 500);
                return;
            }
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => $result['message'] ?? 'Success',
                    'product_id' => $product_id,
                    'attached_count' => isset($result['attached_count']) ? (int)$result['attached_count'] : 0,
                    'restored_count' => isset($result['restored_count']) ? (int)$result['restored_count'] : 0,
                    'duplicate_count' => isset($result['duplicate_count']) ? (int)$result['duplicate_count'] : 0,
                    'total_success' => isset($result['total_success']) ? (int)$result['total_success'] : 0,
                ];
                
                log_message('info', "✅ CONTROLLER RESPONSE: " . json_encode($response));
                $this->output_json($response, 200);
                return;
            }
            
            log_message('error', "❌ Model error: " . ($result['message'] ?? 'Unknown'));
            $this->output_json(['success' => false, 'message' => $result['message'] ?? 'Lỗi gắn ảnh'], 500);
            
        } catch (Exception $e) {
            log_message('error', '❌ CONTROLLER EXCEPTION: ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }


    /**
     * ✅ UPDATE EXISTING: Fix upload() to use new filename format
     * REPLACE the upload() method section with:
     * 
     * This updates existing upload() to use SKU-based filenames
     */
    /*public function upload() {
        try {
            // ✅ 1. Validate file
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Vui lòng chọn file ảnh'
                ], 400);
                return;
            }

            $file = $_FILES['file'];
            $product_id = (int)($this->input->get_post('product_id') ?: 0);

            // Get product to get SKU/code
            if ($product_id > 0) {
                $product = $this->Product_model->get_by_id($product_id);
                if (!$product) {
                    $this->output_json([
                        'success' => false,
                        'message' => 'Product not found'
                    ], 404);
                    return;
                }
                $product_code = $product['code'];
            } else {
                $product_code = 'UNKNOWN';
            }

            // ✅ 2. STRICT MIME + Binary check
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];

            $mime_type = mime_content_type($file['tmp_name']);
            if ($mime_type !== $file['type'] || !isset($allowed_mimes[$mime_type])) {
                log_message('error', 'Invalid MIME: ' . $mime_type . ' vs ' . $file['type']);
                $this->output_json([
                    'success' => false,
                    'message' => 'Chỉ hỗ trợ: JPG, PNG, GIF, WEBP'
                ], 400);
                return;
            }

            // ✅ 3. Check file size
            $max_size = 5 * 1024 * 1024;
            if ($file['size'] < 50 * 1024 || $file['size'] > $max_size) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Kích thước: 50KB - 5MB'
                ], 400);
                return;
            }

            // ✅ 4. Scan for dangerous code
            $file_content = file_get_contents($file['tmp_name'], false, null, 0, 8192);
            $dangerous_patterns = ['/<\?php/i', '/eval\s*\(/i', '/shell_exec\s*\(/i', '/%00/'];
            foreach ($dangerous_patterns as $p) {
                if (preg_match($p, $file_content)) {
                    log_message('error', 'Dangerous code detected: ' . $p);
                    $this->output_json(['success' => false, 'message' => 'File không hợp lệ'], 400);
                    return;
                }
            }

            // ✅ 5. Validate image integrity
            $image_info = getimagesize($file['tmp_name']);
            if (!$image_info || $image_info[0] < 100 || $image_info[1] < 100) {
                $this->output_json([
                    'success' => false,
                    'message' => 'Ảnh tối thiểu 100x100px'
                ], 400);
                return;
            }

            // ✅ 6. Create directory
            $upload_dir = APPPATH . '../uploads/products/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            // ✅ AUTO-CREATE .htaccess if not exists
            $htaccess_path = $upload_dir . '.htaccess';
            if (!file_exists($htaccess_path)) {
                $htaccess_content = <<<'HTACCESS'
                # Only allow images
                <FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
                    Allow from all
                </FilesMatch>

                # Block others
                <FilesMatch "\.php$|\.htaccess$|^\.">
                    Deny from all
                </FilesMatch>

                # Disable PHP
                php_flag engine off
                Options -Indexes

                # Set MIME types
                AddType image/jpeg jpg jpeg
                AddType image/png png
                AddType image/gif gif
                AddType image/webp webp
                HTACCESS;
                
                file_put_contents($htaccess_path, $htaccess_content);
                chmod($htaccess_path, 0644);
                log_message('info', '.htaccess created at: ' . $htaccess_path);
            }

            // ✅ 7. ✨ NEW: Use SKU-based filename format
            $ext = $allowed_mimes[$mime_type];
            $filename = $this->Product_model->format_image_filename($product_code, $ext);
            $filepath = $upload_dir . $filename;

            // ✅ 8. Check path traversal
            $real_upload_dir = realpath($upload_dir);
            if ($real_upload_dir === false) {
                $this->output_json(['success' => false, 'message' => 'Upload directory invalid'], 400);
                return;
            }
            
            $proposed_filepath = $real_upload_dir . DIRECTORY_SEPARATOR . $filename;
            $normalized_path = realpath(dirname($proposed_filepath)) . DIRECTORY_SEPARATOR . $filename;
            
            if (strpos($normalized_path, $real_upload_dir) !== 0) {
                $this->output_json(['success' => false, 'message' => 'Invalid path'], 400);
                return;
            }

            // ✅ 9. Move file + set permissions
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                $this->output_json(['success' => false, 'message' => 'Lỗi lưu file'], 500);
                return;
            }
            chmod($filepath, 0644);

            // ✅ 10. Save to product_images (EXACT SCHEMA)
            $file_url = base_url() . 'uploads/products/' . $filename;
            
            $image_data = [
                'product_id' => $product_id,
                'image_url' => $file_url,
                'image_path' => '/uploads/products/' . $filename,
                'file_name' => $filename,  // ✅ THÊM DÒNG NÀY
                'is_primary' => 0,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('product_images', $image_data);
            $image_id = $this->db->insert_id();

            // ✅ 11. ✨ NEW: Sync products.images field
            if ($product_id > 0) {
                $this->Product_model->sync_product_images($product_id);
            }

            log_message('info', "Upload OK: ID=$image_id, Product=$product_id, SKU=$product_code, File=$filename");

            $this->output_json([
                'success' => true,
                'message' => 'Upload ảnh thành công',
                'data' => [
                    'id' => (int)$image_id,
                    'product_id' => $product_id,
                    'url' => $file_url,
                    'image_path' => '/uploads/products/' . $filename,
                    'is_primary' => 0,
                    'width' => $image_info[0],
                    'height' => $image_info[1],
                    'size' => $file['size']
                ]
            ]);

        } catch (Exception $e) {
            log_message('error', 'Upload failed: ' . $e->getMessage());
            if (isset($filepath) && file_exists($filepath)) {
                @unlink($filepath);
            }
            $this->output_json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    } */

    /**
     * Upload multiple images for product
     * POST /api/products/upload-multiple
     */
    public function upload_multiple_post() {
        try {
            $product_id = (int)($this->input->post('product_id') ?: 0);
            if ($product_id <= 0) {
                $this->output_json(['success' => false, 'message' => 'Missing or invalid product_id'], 400);
                return;
            }
    
            // Kiểm tra sản phẩm tồn tại
            $product = $this->Product_model->get_by_id_full($product_id);
            if (!$product) {
                $this->output_json(['success' => false, 'message' => 'Product not found'], 404);
                return;
            }
    
            if (empty($_FILES['files'])) {
                $this->output_json(['success' => false, 'message' => 'No files uploaded'], 400);
                return;
            }
    
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
    
            $max_size = 5 * 1024 * 1024; // 5MB
            $upload_dir = APPPATH . '../uploads/products/';
    
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
    
                // Kiểm tra MIME type
                $mime_type = mime_content_type($_FILES['file']['tmp_name']);
                if (!isset($allowed_mimes[$mime_type]) || $mime_type !== $_FILES['file']['type']) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => 'Unsupported file type or MIME mismatch'
                    ];
                    continue;
                }
    
                // Kiểm tra kích thước
                if ($_FILES['file']['size'] < 50 * 1024 || $_FILES['file']['size'] > $max_size) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => 'File size must be between 50KB and 5MB'
                    ];
                    continue;
                }
    
                // Generate tên file theo SKU + extension
                $ext = $allowed_mimes[$mime_type];
                $filename = $this->Product_model->format_image_filename($product['code'], $ext);
                $filepath = $upload_dir . $filename;
    
                $this->upload->initialize([
                    'upload_path' => $upload_dir,
                    'allowed_types' => 'jpg|jpeg|png|gif|webp',
                    'max_size' => 5120, // 5MB
                    'file_name' => $filename,
                    'overwrite' => true,
                ]);
    
                if (!$this->upload->do_upload('file')) {
                    $failed_uploads[] = [
                        'file' => $_FILES['file']['name'],
                        'error' => $this->upload->display_errors('', '')
                    ];
                    continue;
                }
    
                // Lưu info ảnh vào db
                $image_data = [
                    'product_id' => $product_id,
                    'image_url' => base_url('uploads/products/' . $filename),
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
                        'error' => 'Failed to save image to database'
                    ];
                }
            }
    
            if (count($uploaded_images) > 0) {
                $this->Product_model->sync_product_images($product_id);
            }
    
            $this->output_json([
                'success' => true,
                'message' => sprintf('Uploaded %d/%d images successfully', count($uploaded_images), $file_count),
                'data' => $uploaded_images,
                'uploaded_count' => count($uploaded_images),
                'failed_count' => count($failed_uploads),
                'errors' => $failed_uploads
            ]);
        } catch (Exception $e) {
            log_message('error', 'Upload multiple images error: ' . $e->getMessage());
            $this->output_json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }    


    /**
     * Delete product image - SOFT or HARD
     * DELETE /api/products/images/:id
     * Query param: ?hard=1 for hard delete (permanent)
     * 
     * ✅ Soft delete: Mark deleted_at (keep file for recovery)
     * ✅ Hard delete: Delete row + file (permanent)
     * ✅ Better error handling and logging
     */
    public function delete_image($image_id) {
        try {
            $image_id = (int)$image_id;
            
            // ✅ Validate image ID
            if (empty($image_id) || $image_id <= 0) {
                log_message('error', "delete_image: Invalid image ID {$image_id}");
                $this->output_json([
                    'success' => false,
                    'message' => 'Image ID không hợp lệ'
                ], 400);
                return;
            }

            // ✅ Check if image exists (allow soft-deleted for hard delete)
            $image = $this->db
                ->where('id', $image_id)
                ->get('product_images')
                ->row_array();
            
            if (!$image) {
                log_message('error', "delete_image: Image {$image_id} not found in database");
                $this->output_json([
                    'success' => false,
                    'message' => "Ảnh ID {$image_id} không tồn tại trong hệ thống. Có thể đã bị xóa trước đó."
                ], 404);
                return;
            }

            // ✅ Log image info for debugging
            log_message('info', "delete_image: Processing image {$image_id}, product_id={$image['product_id']}, deleted_at=" . ($image['deleted_at'] ?? 'NULL'));

            // ✅ Check if hard delete requested
            $hard_delete = $this->input->get('hard') == '1';

            if ($hard_delete) {
                // ==========================================
                // ✅ HARD DELETE - Permanent removal
                // ==========================================
                log_message('info', "DELETE IMAGE HARD: Starting hard delete for image {$image_id}");
                
                $result = $this->Product_model->hard_delete_image($image_id);
                
                if ($result) {
                    log_message('info', "DELETE IMAGE HARD SUCCESS: Image {$image_id} permanently deleted by user {$this->user_data['id']}");
                    $this->output_json([
                        'success' => true,
                        'message' => 'Ảnh đã được xóa vĩnh viễn',
                        'data' => [
                            'image_id' => $image_id,
                            'product_id' => $image['product_id']
                        ]
                    ], 200);
                } else {
                    log_message('error', "DELETE IMAGE HARD FAILED: Cannot delete image {$image_id}");
                    $this->output_json([
                        'success' => false,
                        'message' => 'Không thể xóa ảnh. Vui lòng thử lại.'
                    ], 500);
                }
            } else {
                // ==========================================
                // ✅ SOFT DELETE - Mark deleted_at
                // ==========================================
                
                // Check if already soft-deleted
                if (!empty($image['deleted_at'])) {
                    log_message('info', "delete_image: Image {$image_id} was already soft-deleted at {$image['deleted_at']}");
                    $this->output_json([
                        'success' => true,
                        'message' => 'Ảnh đã được xóa trước đó',
                        'data' => [
                            'image_id' => $image_id,
                            'already_deleted' => true
                        ]
                    ], 200);
                    return;
                }
                
                log_message('info', "DELETE IMAGE SOFT: Starting soft delete for image {$image_id}");
                
                $result = $this->Product_model->soft_delete_image($image_id);

                if ($result) {
                    log_message('info', "DELETE IMAGE SOFT SUCCESS: Image {$image_id} soft deleted by user {$this->user_data['id']}");
                    $this->output_json([
                        'success' => true,
                        'message' => 'Ảnh đã được xóa',
                        'data' => [
                            'image_id' => $image_id,
                            'product_id' => $image['product_id']
                        ]
                    ], 200);
                } else {
                    log_message('error', "DELETE IMAGE SOFT FAILED: Cannot soft delete image {$image_id}");
                    $this->output_json([
                        'success' => false,
                        'message' => 'Không thể xóa ảnh. Vui lòng thử lại.'
                    ], 500);
                }
            }

        } catch (Exception $e) {
            log_message('error', "delete_image EXCEPTION: " . $e->getMessage());
            log_message('error', "delete_image STACK TRACE: " . $e->getTraceAsString());
            
            $this->output_json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
    * API: Lấy chi tiết sản phẩm cha cùng danh sách biến thể, mỗi biến thể có thuộc tính và options kèm tên đầy đủ hiển thị cho frontend
    * URL: GET /api/products/{id}
    */
    public function get_detail_with_variants($product_id){
    
    // 1. Lấy thông tin đầy đủ sản phẩm cha (bao gồm variants_v2)
    $product = $this->Product_model->get_by_id_full($product_id);
    if (!$product) {
        return $this->output_json(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
    }

    // 2. Gắn attribute_values cho từng variant
    if (!empty($product['variants_v2'])) {
        foreach ($product['variants_v2'] as &$variant) {
            $variant['attribute_values'] = $this->Product_attribute_model->get_values_by_variant($variant['id']);
        }
    }

    // 3. Output
    return $this->output_json([
        'success' => true,
        'data' => $product
    ]);
}

} // ← END OF CLASS