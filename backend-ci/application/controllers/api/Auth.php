<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller - Authentication & User Profile API
 * Handles user login, logout, profile, and permissions
 * 
 * FIXED:
 * - Removed fullname (field không tồn tại)
 * - Changed ActivityLogger::create() to ->log()
 */
class Auth extends CI_Controller 
{
    private $current_user;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Role_permission_model');
        $this->load->library('JwtAuth');
        $this->load->library('ActivityLogger');
        
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * LOGIN
     * POST /api/auth/login
     * Authenticate user and generate JWT token
     */
    public function login()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validation
            if (empty($input['username']) || empty($input['password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Username and password are required']);
                return;
            }

            // Tìm user theo username hoặc email
            $this->db->where('deleted_at IS NULL');
            $this->db->group_start();
            $this->db->where('username', $input['username']);
            $this->db->or_where('email', $input['username']);
            $this->db->group_end();
            $user = $this->db->get('users')->row_array();

            if (!$user) {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid credentials']);
                return;
            }

            // Verify password
            if (!password_verify($input['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['message' => 'Invalid credentials']);
                return;
            }

            // Check active status
            if ($user['status'] !== 'active') {
                http_response_code(403);
                echo json_encode(['message' => 'Account is not active']);
                return;
            }

            // Lấy role name
            $this->db->select('roles.name as role_name, roles.description as role_description');
            $this->db->from('model_has_roles');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id');
            $this->db->where('model_has_roles.model_id', $user['id']);
            $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
            $role = $this->db->get()->row_array();

            // Lấy branch name
            $branch = null;
            if ($user['branch_id']) {
                $this->db->select('name');
                $this->db->where('id', $user['branch_id']);
                $branch = $this->db->get('branches')->row_array();
            }

            // Prepare user data cho JWT
            $user_data = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $role['role_name'] ?? 'user',
                'role_description' => $role['role_description'] ?? '',
                'branch_id' => $user['branch_id'],
                'branch_name' => $branch['name'] ?? null,
                'status' => $user['status']
            ];

            // Generate JWT token
            $device_info = $this->input->get_request_header('User-Agent');
            $token = $this->jwtauth->generateToken($user_data, $device_info, 24); // 24 hours

            // Lấy permissions của user
            $permissions = $this->get_user_permissions($user['id'], $role['role_name'] ?? null);

            // Add permissions vào user data response
            $user_data['permissions'] = $permissions;

            // ✅ 🆕 NEW: Transform permissions thành modules structure cho frontend
            $user_data['modules'] = $this->transformPermissionsToModules($permissions);

            // Log activity (FIXED: Dùng ->log() thay vì ::create())
            $this->activitylogger->log('login', 'auth', $user['id'], null, [
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $device_info
            ]);

            // Response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user_data,
                    'token' => $token
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * GET CURRENT USER
     * GET /api/auth/me
     * Get authenticated user profile with permissions
     */
    public function me()
    {
        // Validate JWT token
        $this->current_user = $this->jwtauth->validateToken();
        
        if (!$this->current_user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }

        try {
            $user_id = $this->current_user['id'];

            // Lấy thông tin user từ database
            $this->db->select('users.*, roles.name as role_name, roles.description as role_description, branches.name as branch_name');
            $this->db->from('users');
            $this->db->join('model_has_roles', 'model_has_roles.model_id = users.id', 'left');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id', 'left');
            $this->db->join('branches', 'branches.id = users.branch_id', 'left');
            $this->db->where('users.id', $user_id);
            $this->db->where('users.deleted_at', NULL);
            $user = $this->db->get()->row_array();

            if (!$user) {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
                return;
            }

            // Remove password
            unset($user['password']);

            // Lấy permissions
            $permissions = $this->get_user_permissions($user_id, $user['role_name']);

            // Add permissions to user data
            $user['permissions'] = $permissions;

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => ['user' => $user]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * GET USER PERMISSIONS
     * GET /api/auth/me/permissions
     * Get all permissions of current user
     */
    public function permissions()
    {
        // Validate JWT token
        $this->current_user = $this->jwtauth->validateToken();
        
        if (!$this->current_user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }

        try {
            $user_id = $this->current_user['id'];
            
            // Lấy role của user
            $this->db->select('roles.name as role_name');
            $this->db->from('model_has_roles');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id');
            $this->db->where('model_has_roles.model_id', $user_id);
            $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
            $role = $this->db->get()->row_array();

            $permissions = $this->get_user_permissions($user_id, $role['role_name'] ?? null);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'permissions' => $permissions
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * LOGOUT
     * POST /api/auth/logout
     * Revoke JWT token (remove from sessions table)
     */
    public function logout()
    {
        // Validate JWT token
        $this->current_user = $this->jwtauth->validateToken();
        
        if (!$this->current_user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }

        try {
            // Revoke token
            $token = $this->jwtauth->getTokenFromHeader();
            $this->jwtauth->revokeToken($token);

            // Log activity (FIXED: Dùng ->log())
            $this->activitylogger->log('logout', 'auth', $this->current_user['id'], null, [
                'ip_address' => $this->input->ip_address()
            ]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * CHANGE PASSWORD
     * POST /api/auth/change-password
     * User change their own password
     */
    public function change_password()
    {
        // Validate JWT token
        $this->current_user = $this->jwtauth->validateToken();
        
        if (!$this->current_user) {
            http_response_code(401);
            echo json_encode(['message' => 'Unauthorized']);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validation
            if (empty($input['current_password']) || empty($input['new_password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Current password and new password are required']);
                return;
            }

            // Password strength validation
            if (strlen($input['new_password']) < 8) {
                http_response_code(400);
                echo json_encode(['message' => 'New password must be at least 8 characters']);
                return;
            }

            if (!preg_match('/[A-Z]/', $input['new_password']) || 
                !preg_match('/[a-z]/', $input['new_password']) || 
                !preg_match('/[0-9]/', $input['new_password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Password must contain uppercase, lowercase, and number']);
                return;
            }

            // Get current user
            $user_id = $this->current_user['id'];
            $this->db->where('id', $user_id);
            $user = $this->db->get('users')->row_array();

            // Verify current password
            if (!password_verify($input['current_password'], $user['password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Current password is incorrect']);
                return;
            }

            // Update password
            $this->db->where('id', $user_id);
            $this->db->update('users', [
                'password' => password_hash($input['new_password'], PASSWORD_BCRYPT),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Log activity (FIXED: Dùng ->log())
            $this->activitylogger->log('change-password', 'user', $user_id, [], []);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * HELPER: Get user permissions
     * Super-admin bypass: Trả về tất cả permissions
     * 
     * @param int $user_id
     * @param string $role_name
     * @return array Permission names
     */
    private function get_user_permissions($user_id, $role_name = null)
    {
        // Super-admin bypass
        if ($role_name && in_array(strtolower($role_name), ['super-admin', 'superadmin'])) {
            $this->db->select('name');
            $this->db->where('deleted_at', NULL);
            $query = $this->db->get('permissions');
            $all_permissions = $query->result_array();
            return array_column($all_permissions, 'name');
        }

        // ← FIX: Sử dụng distinct() method thay vì 'DISTINCT ...'
        $this->db->select('permissions.name');  // ← BỎ 'DISTINCT' RA NGOÀI
        $this->db->distinct();                   // ← THÊM DÒNG NÀY
        $this->db->from('permissions');
        $this->db->join('role_has_permissions', 'role_has_permissions.permission_id = permissions.id');
        $this->db->join('model_has_roles', 'model_has_roles.role_id = role_has_permissions.role_id');
        $this->db->where('model_has_roles.model_id', $user_id);
        $this->db->where('permissions.deleted_at', NULL);
        $this->db->order_by('permissions.name', 'ASC');
        
        $query = $this->db->get();
        $permissions = $query->result_array();
        
        return array_column($permissions, 'name');
    }

    /**
     * Transform permissions array into modules structure for frontend
     * Groups permissions by module and module_group
     * 
     * @param array $permissions - Array of permission objects with module & module_group
     * @return array - Modules structure for frontend sidebar
     */
    private function transformPermissionsToModules($permissions) {
        if (empty($permissions)) {
            return [];
        }
        
        $modules_map = [];
        
        foreach ($permissions as $perm) {
            // Skip if no module defined
            if (empty($perm['module'])) {
                continue;
            }
            
            $module_slug = $perm['module'];
            $module_group = $perm['module_group'] ?? 'other';
            
            // ✅ FIX: Force products vào merchandise group
            if ($module_slug === 'products') {
                $module_group = 'merchandise';
            }
            
            // Initialize module if not exists
            if (!isset($modules_map[$module_slug])) {
                $modules_map[$module_slug] = [
                    'slug' => $module_slug,
                    'name' => ucfirst(str_replace('_', ' ', $module_slug)),
                    'path' => '/' . str_replace('_', '-', $module_slug),
                    'group' => $module_group,
                    'permissions' => []
                ];
            }
            
            // Add permission to module
            $modules_map[$module_slug]['permissions'][] = $perm['name'];
        }
        
        // Convert map to array
        return array_values($modules_map);
    }

}