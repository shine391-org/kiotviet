<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Branches extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Branch_model');
        $this->load->library('JwtAuth');  // ← AUTO TẠO $this->jwtauth
    }
    
    // ==================== PUBLIC API METHODS ====================
    
    /**
     * GET /api/branches
     * Lấy danh sách chi nhánh (có phân trang, lọc, sort)
     */
    public function index() {
        // ✅ TRUY CẬP TRỰC TIẾP KHÔNG CẦN KHAI BÁO!
        $this->jwtauth->requirePermission('branches.view');
        
        // Lấy params từ query string
        $params = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status'),
            'page' => (int)$this->input->get('page') ?: 1,
            'limit' => (int)$this->input->get('limit') ?: 20,
            'sort_by' => $this->input->get('sort_by') ?: 'id',
            'sort_order' => $this->input->get('sort_order') ?: 'desc'
        ];
        
        // Lấy data từ model
        $result = $this->Branch_model->get_branches($params);
        
        // Response
        $this->_jsonResponse([
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
    
    /**
     * GET /api/branches/:id
     * Lấy chi nhánh theo ID
     */
    public function view($id) {
        $this->jwtauth->requirePermission('branches.view');
        
        if (!$id) {
            $this->_jsonResponse(['success' => false, 'message' => 'ID is required'], 400);
            return;
        }
        
        $branch = $this->Branch_model->get_by_id($id);
        if ($branch) {
            $this->_jsonResponse(['success' => true, 'data' => $branch], 200);
        } else {
            $this->_jsonResponse(['success' => false, 'message' => 'Branch not found'], 404);
        }
    }
    
    /**
     * POST /api/branches
     * Tạo mới chi nhánh
     */
    public function create() {
        $this->jwtauth->requirePermission('branches.create');
        
        // Get request data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], 400);
            return;
        }
        
        // Validate required fields
        if (empty($data['code']) || empty($data['name'])) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Code và Name là bắt buộc'
            ], 400);
            return;
        }
        
        // Check code đã tồn tại chưa
        if ($this->Branch_model->check_code_exists($data['code'])) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Mã chi nhánh đã tồn tại'
            ], 400);
            return;
        }
        
        // Get current user
        $user = $this->jwtauth->validateToken();
        
        // Add timestamp
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $user['id'] ?? null;
        $data['status'] = $data['status'] ?? 'active';
        
        // Insert to database
        $id = $this->Branch_model->insert($data);
        
        if ($id) {
            $this->_jsonResponse([
                'success' => true,
                'message' => 'Branch created successfully',
                'data' => $this->Branch_model->get_by_id($id)
            ], 201);
        } else {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Failed to create branch'
            ], 500);
        }
    }
    
    /**
     * PUT /api/branches/:id
     * Cập nhật chi nhánh
     */
    public function update($id) {
        $this->jwtauth->requirePermission('branches.edit');
        
        if (!$id) {
            $this->_jsonResponse(['success' => false, 'message' => 'ID is required'], 400);
            return;
        }
        
        // Get old data
        $old_data = $this->Branch_model->get_by_id($id);
        if (!$old_data) {
            $this->_jsonResponse(['success' => false, 'message' => 'Branch not found'], 404);
            return;
        }
        
        // Get request data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            $this->_jsonResponse(['success' => false, 'message' => 'No data received'], 400);
            return;
        }
        
        // BUSINESS RULE: Không thể ngừng hoạt động chi nhánh mặc định
        if (isset($old_data['is_default']) && $old_data['is_default'] == 1 && 
            isset($data['status']) && $data['status'] === 'inactive') {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Không thể ngừng hoạt động chi nhánh mặc định'
            ], 400);
            return;
        }
        
        // Check code mới có trùng không (nếu đổi code)
        if (isset($data['code']) && $data['code'] !== $old_data['code']) {
            if ($this->Branch_model->check_code_exists($data['code'])) {
                $this->_jsonResponse([
                    'success' => false,
                    'message' => 'Mã chi nhánh đã tồn tại'
                ], 400);
                return;
            }
        }
        
        // Get current user
        $user = $this->jwtauth->validateToken();
        
        // Add timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $user['id'] ?? null;
        
        // Update
        $updated = $this->Branch_model->update($id, $data);
        
        if ($updated) {
            $new_data = $this->Branch_model->get_by_id($id);
            $this->_jsonResponse([
                'success' => true,
                'message' => 'Branch updated successfully',
                'data' => $new_data
            ], 200);
        } else {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Update failed or no changes made'
            ], 400);
        }
    }
    
    /**
     * DELETE /api/branches/:id
     * Xóa chi nhánh
     */
    public function delete($id) {
        $this->jwtauth->requirePermission('branches.delete');
        
        if (!$id) {
            $this->_jsonResponse(['success' => false, 'message' => 'ID is required'], 400);
            return;
        }
        
        $branch = $this->Branch_model->get_by_id($id);
        if (!$branch) {
            $this->_jsonResponse(['success' => false, 'message' => 'Branch not found'], 404);
            return;
        }
        
        // BUSINESS RULE 1: Không thể xóa chi nhánh mặc định
        if (isset($branch['is_default']) && $branch['is_default'] == 1) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Không thể xóa chi nhánh mặc định'
            ], 400);
            return;
        }
        
        // BUSINESS RULE 2: Phải có ít nhất 1 chi nhánh
        $total = $this->Branch_model->count_all();
        if ($total <= 1) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Không thể xóa chi nhánh cuối cùng'
            ], 400);
            return;
        }
        
        // Delete
        $deleted = $this->Branch_model->delete($id);
        
        if ($deleted) {
            $this->_jsonResponse([
                'success' => true,
                'message' => 'Branch deleted successfully'
            ], 200);
        } else {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Delete failed'
            ], 500);
        }
    }
    
    /**
     * POST /api/branches/set_default/:id
     * Đặt chi nhánh làm mặc định
     */
    public function set_default($id) {
        $this->jwtauth->requirePermission('branches.edit');
        
        if (!$id) {
            $this->_jsonResponse(['success' => false, 'message' => 'ID is required'], 400);
            return;
        }
        
        // Check branch tồn tại
        $branch = $this->Branch_model->get_by_id($id);
        if (!$branch) {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Không tìm thấy chi nhánh'
            ], 404);
            return;
        }
        
        // Set default
        if ($this->Branch_model->set_default($id)) {
            $this->_jsonResponse([
                'success' => true,
                'message' => 'Đã đặt chi nhánh mặc định',
                'data' => $this->Branch_model->get_by_id($id)
            ], 200);
        } else {
            $this->_jsonResponse([
                'success' => false,
                'message' => 'Lỗi khi đặt mặc định'
            ], 500);
        }
    }
    
    // ==================== HELPER METHODS ====================
    
    private function _jsonResponse($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }
}