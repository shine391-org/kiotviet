<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Roles Controller - Refactored to use MY_Controller
 * Quản lý roles (vai trò) trong hệ thống
 */
class Roles extends MY_Controller {

    protected $module_name = 'roles';
    
    // Protected roles (không thể xóa/sửa)
    private $protected_roles = ['super-admin', 'admin'];

    public function __construct() {
        parent::__construct();
        $this->load->model('Role_permission_model');
    }

    /**
     * Check if role name is protected
     */
    private function is_protected_role($role_name) {
        return in_array(strtolower($role_name), array_map('strtolower', $this->protected_roles));
    }

    // ========== LIST ROLES ==========
    /**
     * GET /api/roles
     * List all roles with user count
     */
    public function index() {
        $this->requirePermission('roles.view');
        
        try {
            $search = $this->input->get('search') ?? '';
            $page = (int)($this->input->get('page') ?? 1);
            $limit = (int)($this->input->get('limit') ?? 10);
            $offset = ($page - 1) * $limit;

            // Build query với user count
            $this->db->select('roles.*, COUNT(model_has_roles.model_id) as user_count');
            $this->db->from('roles');
            $this->db->join('model_has_roles', 'model_has_roles.role_id = roles.id AND model_has_roles.model_type = "App\\\\Models\\\\User"', 'left');
            $this->db->where('roles.deleted_at IS NULL');

            // Search filter
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('roles.name', $search);
                $this->db->or_like('roles.description', $search);
                $this->db->group_end();
            }

            $this->db->group_by('roles.id');

            // Get total
            $total_query = clone $this->db;
            $total = $total_query->count_all_results('', FALSE);

            // Get data with pagination
            $this->db->limit($limit, $offset);
            $this->db->order_by('roles.id', 'ASC');
            $query = $this->db->get();
            $roles = $query->result_array();

            $this->_jsonResponse([
                'data' => $roles,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    // ========== GET ROLE BY ID ==========
    /**
     * GET /api/roles/{id}
     * Get role detail by ID
     */
    public function show($id) {
        $this->requirePermission('roles.view');
        
        try {
            $this->db->select('roles.*');
            $this->db->from('roles');
            $this->db->where('roles.id', $id);
            $this->db->where('roles.deleted_at IS NULL');
            $query = $this->db->get();
            $role = $query->row_array();

            if (!$role) {
                $this->_jsonResponse(['message' => 'Role not found'], 404);
                return;
            }

            // Lấy số lượng users có role này
            $this->db->where('role_id', $id);
            $this->db->where('model_type', 'App\\Models\\User');
            $user_count = $this->db->count_all_results('model_has_roles');
            $role['user_count'] = $user_count;

            $this->_jsonResponse(['data' => $role], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== CREATE ROLE ==========
    /**
     * POST /api/roles/create
     * Create new role
     */
    public function create() {
        $this->requirePermission('roles.create');
        
        // ✅ Get raw input
        $raw_input = file_get_contents('php://input');
        
        // ✅ Check if raw input is empty
        if (empty($raw_input)) {
            $this->_jsonResponse(['message' => 'No data received'], 400);
            return;
        }

        // ✅ Parse JSON
        $input = json_decode($raw_input, true);
        
        // ✅ Check JSON decode error
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->_jsonResponse(['message' => 'Invalid JSON: ' . json_last_error_msg()], 400);
            return;
        }

        // ✅ Check if input is array
        if (!is_array($input)) {
            $this->_jsonResponse(['message' => 'Input must be a JSON object'], 400);
            return;
        }

        // ✅ Validation với isset() thay vì empty()
        if (!isset($input['name']) || trim($input['name']) === '') {
            $this->_jsonResponse(['message' => 'Name is required'], 400);
            return;
        }

        if (!isset($input['description']) || trim($input['description']) === '') {
            $this->_jsonResponse(['message' => 'Description is required'], 400);
            return;
        }

        // Trim input
        $input['name'] = trim($input['name']);
        $input['description'] = trim($input['description']);

        // Check name unique
        $this->db->where('name', $input['name']);
        $this->db->where('deleted_at IS NULL');
        $existing = $this->db->get('roles');
        
        if ($existing->num_rows() > 0) {
            $this->_jsonResponse(['message' => 'Role name already exists'], 400);
            return;
        }

        try {
            $role_data = [
                'name' => $input['name'],
                'description' => $input['description'],
                'guard_name' => $input['guard_name'] ?? 'web',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('roles', $role_data);
            $role_id = $this->db->insert_id();

            // Ghi log
            $this->activitylogger->created('role', $role_id, $role_data);

            $this->_jsonResponse([
                'message' => 'Role created successfully',
                'role_id' => $role_id
            ], 201);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== UPDATE ROLE ==========
    /**
     * PUT /api/roles/update/{id}
     * Update role
     */
    public function update($id) {
        $this->requirePermission('roles.edit');
        
        $input = json_decode(file_get_contents('php://input'), true);

        // Check role exists
        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $old_role = $this->db->get('roles')->row_array();

        if (!$old_role) {
            $this->_jsonResponse(['message' => 'Role not found'], 404);
            return;
        }

        // Check if role is protected
        if ($this->is_protected_role($old_role['name'])) {
            $this->_jsonResponse([
                'message' => 'Cannot modify protected role. This role is system protected.'
            ], 403);
            return;
        }

        // Validate name unique (nếu đổi name)
        if (!empty($input['name']) && $input['name'] !== $old_role['name']) {
            $this->db->where('name', $input['name']);
            $this->db->where('id !=', $id);
            $this->db->where('deleted_at IS NULL');
            $existing = $this->db->get('roles');
            
            if ($existing->num_rows() > 0) {
                $this->_jsonResponse(['message' => 'Role name already exists'], 400);
                return;
            }
        }

        try {
            $role_data = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($input['name'])) $role_data['name'] = $input['name'];
            if (!empty($input['description'])) $role_data['description'] = $input['description'];
            if (!empty($input['guard_name'])) $role_data['guard_name'] = $input['guard_name'];

            $this->db->where('id', $id);
            $this->db->update('roles', $role_data);

            // Ghi log
            $this->activitylogger->updated('role', $id, $old_role, $role_data);

            $this->_jsonResponse(['message' => 'Role updated successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== DELETE ROLE ==========
    /**
     * DELETE /api/roles/delete/{id}
     * Soft delete role
     */
    public function delete($id) {
        $this->requirePermission('roles.delete');
        
        // Check role exists
        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $role = $this->db->get('roles')->row_array();

        if (!$role) {
            $this->_jsonResponse(['message' => 'Role not found'], 404);
            return;
        }

        // Check if role is protected
        if ($this->is_protected_role($role['name'])) {
            $this->_jsonResponse([
                'message' => 'Cannot delete protected role. This role is system protected.'
            ], 403);
            return;
        }

        // Kiểm tra có users đang dùng role này không
        $this->db->where('role_id', $id);
        $this->db->where('model_type', 'App\\Models\\User');
        $user_count = $this->db->count_all_results('model_has_roles');
        
        if ($user_count > 0) {
            $this->_jsonResponse([
                'message' => "Cannot delete role. It is assigned to {$user_count} user(s)",
                'user_count' => $user_count
            ], 400);
            return;
        }

        try {
            // Soft delete
            $this->db->where('id', $id);
            $this->db->update('roles', ['deleted_at' => date('Y-m-d H:i:s')]);

            // Ghi log
            $this->activitylogger->deleted('role', $id, $role);

            $this->_jsonResponse(['message' => 'Role deleted successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== GET PERMISSIONS OF ROLE ==========
    /**
     * GET /api/roles/{id}/permissions
     * Get permissions assigned to role
     */
    public function permissions($role_id) {
        $this->requirePermission('roles.view');
        
        try {
            // Lấy role
            $this->db->where('id', $role_id);
            $this->db->where('deleted_at IS NULL');
            $role = $this->db->get('roles')->row_array();

            if (!$role) {
                $this->_jsonResponse(['message' => 'Role not found'], 404);
                return;
            }

            // Lấy permissions của role
            $this->db->select('permissions.*');
            $this->db->from('permissions');
            $this->db->join('role_has_permissions', 'role_has_permissions.permission_id = permissions.id');
            $this->db->where('role_has_permissions.role_id', $role_id);
            $this->db->where('permissions.deleted_at IS NULL');
            $this->db->order_by('permissions.module', 'ASC');
            $this->db->order_by('permissions.name', 'ASC');
            $query = $this->db->get();
            $permissions = $query->result_array();

            $this->_jsonResponse([
                'role' => $role,
                'permissions' => $permissions
            ], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== ASSIGN PERMISSIONS TO ROLE ==========
    /**
     * POST /api/roles/{id}/assign-permissions
     * Assign permissions to role
     */
    public function assign_permissions($role_id) {
        $this->requirePermission('roles.edit');
        
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['permission_ids']) || !is_array($input['permission_ids'])) {
            $this->_jsonResponse(['message' => 'permission_ids array is required'], 400);
            return;
        }

        try {
            $this->db->trans_start();

            // Xóa tất cả permissions cũ
            $this->db->where('role_id', $role_id);
            $this->db->delete('role_has_permissions');

            // Thêm permissions mới
            foreach ($input['permission_ids'] as $permission_id) {
                $this->db->insert('role_has_permissions', [
                    'role_id' => $role_id,
                    'permission_id' => $permission_id
                ]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            // Clear permission cache
            $this->clearPermissionCache();

            // Ghi log
            
            $this->activitylogger->log('assign-permissions', 'role', $role_id, [
                'permission_ids' => $input['permission_ids'],
                'permission_count' => count($input['permission_ids'])
            ]);

            $this->_jsonResponse(['message' => 'Permissions assigned successfully'], 200);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== GET ALL PERMISSIONS ==========
    /**
     * GET /api/permissions
     * Get all permissions grouped by module_group > module
     */
    public function getallpermissions() {
        $this->requirePermission('roles.view');
    
        try {
            // 1. Get all module groups
            $this->db->select('id, name, display_name, description, icon, order');
            $this->db->from('module_groups');
            $this->db->where('is_active', 1);
            $this->db->order_by('order', 'ASC');
            $module_groups_query = $this->db->get();
            $module_groups = $module_groups_query->result_array();
            
            // 2. Get all permissions
            $this->db->select('id, name, display_name, module, module_group, description');
            $this->db->from('permissions');
            $this->db->where('deleted_at IS NULL');
            $this->db->order_by('module_group', 'ASC');
            $this->db->order_by('module', 'ASC');
            $this->db->order_by('display_name', 'ASC');
            $permissions_query = $this->db->get();
            $permissions = $permissions_query->result_array();
            
            // 3. Structure data: module_groups > modules > permissions
            $result = [];
            
            foreach ($module_groups as $group) {
                $group_name = $group['name'];
                
                // Get modules belonging to this group
                $modules_in_group = [];
                $current_module = null;
                
                foreach ($permissions as $permission) {
                    if ($permission['module_group'] === $group_name) {
                        $module_name = $permission['module'];
                        
                        if (!isset($modules_in_group[$module_name])) {
                            $modules_in_group[$module_name] = [
                                'module' => $module_name,
                                'permissions' => []
                            ];
                        }
                        
                        $modules_in_group[$module_name]['permissions'][] = [
                            'id' => $permission['id'],
                            'name' => $permission['name'],
                            'display_name' => $permission['display_name'],
                            'description' => $permission['description']
                        ];
                    }
                }
                
                if (!empty($modules_in_group)) {
                    $result[] = [
                        'group_id' => $group['id'],
                        'group_name' => $group['name'],
                        'group_display_name' => $group['display_name'],
                        'group_icon' => $group['icon'],
                        'group_description' => $group['description'],
                        'modules' => array_values($modules_in_group)
                    ];
                }
            }
            
            $this->_jsonResponse(['data' => $result], 200);
            
        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Clear permission cache for all users
     */
    private function clearPermissionCache() {
        $cache_dir = APPPATH . 'cache/permissions/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}