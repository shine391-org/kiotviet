<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Product Categories Controller - FIXED VERSION
 * 
 * ✅ Uses standard CI_Controller (consistent with system)
 * ✅ No REST_Controller dependency
 * ✅ Same pattern as Products.php, Branches.php
 * ✅ Full JWT authentication
 * 
 * @package    CodeIgniter
 * @subpackage Controllers
 * @category   Product Categories API
 * @author     LANOCRM Development Team
 * @version    2.0.0 - PRODUCTION READY
 */
class Product_categories extends CI_Controller {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Load models
        $this->load->model('Product_category_model');
        
        // Load JWT library
        $this->load->library('JwtAuth');
    }
    
    /**
     * Output JSON response helper
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    private function json_response($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }
    
    /**
     * GET - List categories
     * 
     * URL: GET /api/product-categories
     * Query params:
     *   - view=tree : Return tree structure with children
     *   - (default) : Return flat list with pagination
     * 
     * @return void
     */
    public function index() {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
    
            // Check view type
            $view = $this->input->get('view');
            $include_deleted = $this->input->get('include_deleted') === 'true';
    
            if ($view === 'tree') {
                // ✅ TRUYỀN thêm biến $include_deleted vào get_tree
                $tree = $this->Product_category_model->get_tree(null, 0, $include_deleted);
                
                $this->json_response([
                    'success' => true,
                    'data' => $tree
                ], 200);
                
            } else {
                // ✅ Return flat list with pagination
                $params = [
                    'search' => $this->input->get('search'),
                    'include_deleted' => $include_deleted,
                    'level' => $this->input->get('level'),
                    'parent_id' => $this->input->get('parent_id'),
                    'status' => $this->input->get('status'),
                    'page' => $this->input->get('page') ? (int)$this->input->get('page') : 1,
                    'limit' => $this->input->get('limit') ? (int)$this->input->get('limit') : 50
                ];
                
                $result = $this->Product_category_model->get_categories($params);
                
                // ✅ NEW: Add product_count to each category in flat list
                foreach ($result['data'] as &$category) {
                    $category['product_count'] = $this->Product_category_model->count_products(
                        $category['id'], 
                        true  // Include children
                    );
                }
                unset($category); // Break reference
                
                $this->json_response([
                    'success' => true,
                    'data' => $result['data'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $params['page'],
                        'limit' => $params['limit'],
                        'total_pages' => ceil($result['total'] / $params['limit'])
                    ]
                ], 200);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::index - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách danh mục: ' . $e->getMessage()
            ], 500);
        }
    }        
    
    /**
     * GET - Single category by ID
     * 
     * URL: GET /api/product-categories/:id
     * 
     * @param int $id Category ID
     * @return void
     */
    public function detail($id) {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
            
            if (empty($id)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'ID danh mục không hợp lệ'
                ], 400);
            }
            
            $category = $this->Product_category_model->get_by_id($id);
            
            if (!$category) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }
            
            // Get additional info
            $category['children'] = $this->Product_category_model->get_children($id);
            $category['breadcrumb'] = $this->Product_category_model->get_breadcrumb($id);
            $category['product_count'] = $this->Product_category_model->count_products($id, true);
            
            $this->json_response([
                'success' => true,
                'data' => $category
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::detail - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết danh mục: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST - Create new category
     * 
     * URL: POST /api/product-categories
     * 
     * @return void
     */
    public function create() {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
            
            // ✅ Get POST data
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            if (empty($data) || !is_array($data)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không có dữ liệu để tạo danh mục'
                ], 400);
            }
            
            // Validate required fields
            if (empty($data['name']) || !is_string($data['name'])) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Tên danh mục không được để trống'
                ], 400);
            }
            
            // ✅ Generate code with uniqueness check
            if (empty($data['code'])) {
                $data['code'] = $this->Product_category_model->generate_code($data['name']);
            } else {
                if ($this->Product_category_model->code_exists($data['code'])) {
                    $this->json_response([
                        'success' => false,
                        'message' => 'Mã danh mục đã tồn tại: ' . $data['code']
                    ], 400);
                }
            }
            
            // ✅ Generate slug with uniqueness check
            if (empty($data['slug'])) {
                $data['slug'] = $this->Product_category_model->generate_slug($data['name']);
            } else {
                if ($this->Product_category_model->slug_exists($data['slug'])) {
                    $data['slug'] = $this->Product_category_model->generate_slug($data['name']);
                }
            }
            
            // Validate parent_id and level
            if (!empty($data['parent_id'])) {
                $parent = $this->Product_category_model->get_by_id($data['parent_id']);
                
                if (!$parent) {
                    $this->json_response([
                        'success' => false,
                        'message' => 'Danh mục cha không tồn tại'
                    ], 400);
                }
                
                $data['level'] = $parent['level'] + 1;
                
                // ✅ CODE MỚI - Không giới hạn cấp độ
                // Level được tự động tính từ parent
                if (!empty($data['parent_id'])) {
                    $parent = $this->Product_category_model->get_by_id($data['parent_id']);
                    if (!$parent) {
                        $this->json_response([
                            'success' => false,
                            'message' => 'Danh mục cha không tồn tại'
                        ], 400);
                    }
                    
                    $data['level'] = $this->Product_category_model->calculate_level($data['parent_id']);
                } else {
                    $data['parent_id'] = null;
                    $data['level'] = 1;
                }

            } else {
                $data['parent_id'] = null;
                $data['level'] = 1;
            }
            
            // Set defaults
            $data['status'] = !empty($data['status']) ? $data['status'] : 'active';
            $data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Remove unwanted fields
            unset($data['id']);
            unset($data['deleted_at']);
            
            // ✅ Clean empty optional fields
            $optional_fields = ['description', 'image'];
            foreach ($optional_fields as $field) {
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }
            
            // Insert
            $category_id = $this->Product_category_model->insert($data);
            
            if ($category_id) {
                $category = $this->Product_category_model->get_by_id($category_id);
                
                $this->json_response([
                    'success' => true,
                    'message' => 'Tạo danh mục thành công',
                    'data' => $category
                ], 201);
            } else {
                $this->json_response([
                    'success' => false,
                    'message' => 'Tạo danh mục thất bại'
                ], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::create - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi tạo danh mục: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT - Update category
     * 
     * URL: PUT /api/product-categories/:id
     * 
     * @param int $id Category ID
     * @return void
     */
    public function update($id) {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
            
            if (empty($id)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'ID danh mục không hợp lệ'
                ], 400);
            }
            
            // Check if exists
            $category = $this->Product_category_model->get_by_id($id);
            
            if (!$category) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }
            
            // ✅ Get PUT data
            $raw_input = file_get_contents('php://input');
            $data = json_decode($raw_input, true);
            
            if (empty($data) || !is_array($data)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không có dữ liệu để cập nhật'
                ], 400);
            }
            
            // Validate name if provided
            if (isset($data['name'])) {
                if (empty($data['name']) || !is_string($data['name'])) {
                    $this->json_response([
                        'success' => false,
                        'message' => 'Tên danh mục không được để trống'
                    ], 400);
                }
                
                // Regenerate slug if name changed
                if ($data['name'] !== $category['name'] && empty($data['slug'])) {
                    $data['slug'] = $this->Product_category_model->generate_slug($data['name'], $id);
                }
            }
            
            // Check code if provided
            if (isset($data['code']) && $data['code'] !== $category['code']) {
                if ($this->Product_category_model->code_exists($data['code'], $id)) {
                    $this->json_response([
                        'success' => false,
                        'message' => 'Mã danh mục đã tồn tại: ' . $data['code']
                    ], 400);
                }
            }
            
            // Check slug if provided
            if (isset($data['slug']) && $data['slug'] !== $category['slug']) {
                if ($this->Product_category_model->slug_exists($data['slug'], $id)) {
                    $this->json_response([
                        'success' => false,
                        'message' => 'Slug đã tồn tại: ' . $data['slug']
                    ], 400);
                }
            }
            
            // Validate parent_id change
            if (isset($data['parent_id']) && $data['parent_id'] != $category['parent_id']) {
                if (!empty($data['parent_id'])) {
                    $parent = $this->Product_category_model->get_by_id($data['parent_id']);
                    
                    if (!$parent) {
                        $this->json_response([
                            'success' => false,
                            'message' => 'Danh mục cha không tồn tại'
                        ], 400);
                    }
                    
                    if ($data['parent_id'] == $id) {
                        $this->json_response([
                            'success' => false,
                            'message' => 'Không thể đặt danh mục làm cha của chính nó'
                        ], 400);
                    }
                    
                    $data['level'] = (int)($parent['level'] + 1);
                    
                    // ✅ CODE MỚI - Kiểm tra circular reference thay vì giới hạn cấp
                    if ($this->Product_category_model->is_descendant($id, $data['parent_id'])) {
                        $this->json_response([
                            'success' => false,
                            'message' => 'Không thể di chuyển danh mục vào chính danh mục con của nó'
                        ], 400);
                    }

                    $data['level'] = $this->Product_category_model->calculate_level($data['parent_id']);
                } else {
                    $data['parent_id'] = null;
                    $data['level'] = 1;
                }
            }
            
            // Set timestamp
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Remove protected fields
            unset($data['id']);
            unset($data['created_at']);
            unset($data['deleted_at']);
            
            // ✅ Clean empty optional fields
            $optional_fields = ['description', 'image'];
            foreach ($optional_fields as $field) {
                if (isset($data[$field]) && $data[$field] === '') {
                    $data[$field] = null;
                }
            }
            
            // Update
            $success = $this->Product_category_model->update($id, $data);
            
            if ($success) {
                $updated_category = $this->Product_category_model->get_by_id($id);
                
                $this->json_response([
                    'success' => true,
                    'message' => 'Cập nhật danh mục thành công',
                    'data' => $updated_category
                ], 200);
            } else {
                $this->json_response([
                    'success' => false,
                    'message' => 'Cập nhật danh mục thất bại'
                ], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::update - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi cập nhật danh mục: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE - Soft delete category
     * 
     * URL: DELETE /api/product-categories/:id
     * 
     * @param int $id Category ID
     * @return void
     */
    public function delete($id) {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
            
            if (empty($id)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'ID danh mục không hợp lệ'
                ], 400);
            }
            
            // Check if exists
            $category = $this->Product_category_model->get_by_id($id);
            
            if (!$category) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không tìm thấy danh mục'
                ], 404);
            }
            
            // Check if has children
            $children = $this->Product_category_model->get_children($id);
            
            if (!empty($children)) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không thể xóa danh mục có danh mục con. Vui lòng xóa danh mục con trước.'
                ], 400);
            }
            
            // Check if has products
            $product_count = $this->Product_category_model->count_products($id);
            
            if ($product_count > 0) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Không thể xóa danh mục có ' . $product_count . ' sản phẩm. Vui lòng di chuyển sản phẩm trước.'
                ], 400);
            }
            
            // Soft delete
            $success = $this->Product_category_model->soft_delete($id);
            
            if ($success) {
                $this->json_response([
                    'success' => true,
                    'message' => 'Xóa danh mục thành công'
                ], 200);
            } else {
                $this->json_response([
                    'success' => false,
                    'message' => 'Xóa danh mục thất bại'
                ], 500);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::delete - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi xóa danh mục: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET - Get products in category (including children)
     * URL: GET /api/product-categories/:id/products
     */
    public function products($category_id) {
        try {
            $user = $this->jwtauth->validateToken();
            if (!$user) {
                $this->json_response(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            
            // Lấy tất cả ID category con
            $category_ids = $this->Product_category_model->get_all_descendant_ids($category_id, true);
            
            // Load Products model
            $this->load->model('Product_model');
            
            // Query products với join bảng links
            $this->db->select('products.id, products.name, products.code, products.selling_price, products.status, products.image');
            $this->db->from('products');
            $this->db->join('product_category_links pcl', 'pcl.product_id = products.id');
            $this->db->where_in('pcl.category_id', $category_ids);
            $this->db->where('products.deleted_at IS NULL');
            $this->db->order_by('products.name', 'ASC');
            $products = $this->db->get()->result_array();
            
            $this->json_response([
                'success' => true,
                'data' => $products,
                'category_ids' => $category_ids
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::products - ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }    

    
    /**
     * GET - Get statistics
     * 
     * URL: GET /api/product-categories/statistics
     * 
     * @return void
     */
    public function statistics() {
        try {
            // ✅ Authenticate
            $user = $this->jwtauth->validateToken();
            
            if (!$user) {
                $this->json_response([
                    'success' => false,
                    'message' => 'Unauthorized - Invalid or missing token'
                ], 401);
            }
            
            $stats = $this->Product_category_model->get_statistics();
            
            $this->json_response([
                'success' => true,
                'data' => $stats
            ], 200);
            
        } catch (Exception $e) {
            log_message('error', 'Product_categories::statistics - ' . $e->getMessage());
            
            $this->json_response([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()
            ], 500);
        }
    }
    // hard delete product category
    public function hard_delete_delete($id) {
        try {
            $user = $this->jwtauth->validateToken();
            if (!$user) {
                $this->json_response(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            if (empty($id)) {
                $this->json_response(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            }
            $success = $this->Product_category_model->hard_delete($id);
            if ($success) {
                $this->json_response(['success' => true, 'message' => 'Đã xoá vĩnh viễn.'], 200);
            } else {
                $this->json_response(['success' => false, 'message' => 'Xoá vĩnh viễn thất bại'], 500);
            }
        } catch (Exception $e) {
            $this->json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Restore soft delete category
    public function restore_put($id) {
        try {
            $user = $this->jwtauth->validateToken();
            if (!$user) {
                $this->json_response(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            if (empty($id)) {
                $this->json_response(['success' => false, 'message' => 'ID không hợp lệ'], 400);
            }
            $success = $this->Product_category_model->restore($id);
            if ($success) {
                $this->json_response(['success' => true, 'message' => 'Phục hồi thành công'], 200);
            } else {
                $this->json_response(['success' => false, 'message' => 'Khôi phục thất bại'], 500);
            }
        } catch (Exception $e) {
            $this->json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    Thêm hàm mới products_with_variants($category_id) trả về danh sách sản phẩm + biến thể. Hàm này:
    Xác thực token.
    Gọi hàm model mới get_products_with_variants_by_category.
    Trả JSON dữ liệu.
    */
    public function products_with_variants($category_id) {
        try {
            $user = $this->jwtauth->validateToken();
            if (!$user) {
                $this->json_response(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            if (empty($category_id)) {
                $this->json_response(['success' => false, 'message' => 'Invalid category id'], 400);
            }
    
            $products = $this->Product_category_model->get_products_with_variants_by_category($category_id);
    
            $this->json_response([
                'success' => true,
                'data' => $products
            ], 200);
    
        } catch (Exception $e) {
            log_message('error', 'Product_categories::products_with_variants - ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }    
}