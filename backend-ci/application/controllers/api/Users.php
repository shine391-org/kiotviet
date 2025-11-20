<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Users Controller - Refactored to use MY_Controller
 * Quản lý users, authentication, profile, permissions
 */
class Users extends MY_Controller {

    protected $module_name = 'users';
    //private $current_user;

    public function __construct() {
        parent::__construct();
        
        $this->load->model('User_model');
        $this->load->library('JwtAuth');
        $this->load->model('Role_permission_model');
        $this->load->model('Password_history_model');
        $this->load->library('RateLimiter');
        $this->load->helper('validation');

        // Bypass JWT cho public endpoints
        $method = $this->router->fetch_method();
        $public_methods = ['login', 'forgot_password', 'reset_password'];
        
        if (!in_array($method, $public_methods)) {
            if (!$this->current_user) {
                $this->_jsonResponse(['message' => 'Unauthorized'], 401);
            }
        }
    }

    // ========== HELPER FUNCTIONS ==========
    /* hàm đã có trong MY_Controller.php
    private function check_roles($allowed_roles = ['admin']) {
        if (
            !isset($this->current_user['role']) ||
            !in_array(strtolower($this->current_user['role']), array_map('strtolower', $allowed_roles))
        ) {
            $this->_jsonResponse(['message' => 'Forbidden - Insufficient privileges'], 403);
        }
    }
    */
    // ========== AUTHENTICATION ==========
    
    /**
     * POST /api/users/login
     * Login user
     */
    public function login() {
        // Parse JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        // Validate input
        if (empty($input['username']) || empty($input['password'])) {
            $this->_jsonResponse(['message' => 'Username and password are required'], 400);
            return;
        }
    
        // Get IP address
        $ip = $this->input->ip_address();
    
        try {
            // 1. RATE LIMITING CHECK
            if (!$this->ratelimiter->check($ip, 'login', 5, 300)) {
                $this->_jsonResponse([
                    'message' => 'Too many login attempts. Please try again later.'
                ], 429);
                return;
            }
    
            // 2. GET USER BY USERNAME
            $user = $this->User_model->get_by_username($input['username']);
    
            // 3. VERIFY CREDENTIALS
            if (!$user || !password_verify($input['password'], $user['password'])) {
                $this->_jsonResponse(['message' => 'Invalid credentials'], 401);
                return;
            }
    
            // 4. CHECK ACCOUNT STATUS
            if ($user['status'] !== 'active') {
                $this->_jsonResponse(['message' => 'Account is inactive'], 403);
                return;
            }
    
            // 5. GET USER ROLE
            $user_role = $this->Role_permission_model->getUserRole($user['id']);
            $role_name = $user_role ? $user_role['name'] : null;
    
            // 6. GENERATE JWT TOKEN
            $token = $this->jwtauth->generateToken([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $role_name,
                'branch_id' => $user['branch_id']
            ]);
    
            // 7. UPDATE LAST LOGIN - ✅ CẢ 2 TRƯỜNG
            $this->User_model->update($user['id'], [
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => $ip
            ]);
    
            // 8. LOG ACTIVITY
            $this->activitylogger->log('login', 'user', $user['id'], [
                'ip' => $ip,
                'user_agent' => $this->input->user_agent()
            ]);
    
            // 9. GET PERMISSIONS - ✅ BỔ SUNG module và module_group
            $permissions = $this->Role_permission_model->getUserPermissions($user['id']);
            
            // ✅ NEW: Get full permission details (id, name, module, module_group)
            $permissions_full = [];
            foreach ($permissions as $perm) {
                $permissions_full[] = [
                    'id' => $perm['id'],
                    'name' => $perm['name'],
                    'display_name' => $perm['display_name'] ?? $perm['name'],
                    'module' => $perm['module'] ?? $this->extractModule($perm['name']),
                    'module_group' => $perm['module_group'] ?? null
                ];
            }
            
            // ✅ KEEP: permission_names for backward compatibility
            $permission_names = array_column($permissions, 'name');
    
            // 10. RETURN SUCCESS RESPONSE
            $this->_jsonResponse([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $role_name,
                    'branch_id' => $user['branch_id'],
                    'permissions' => $permissions_full  // ✅ CHANGED: Full objects instead of names
                ]
            ], 200);
    
        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * ✅ HELPER METHOD: Extract module from permission name
     * Example: "branches.create" => "branches"
     */
    private function extractModule($permission_name) {
        $parts = explode('.', $permission_name);
        return $parts[0] ?? null;
    }    


    /**
     * POST /api/users/logout
     * Logout user
     */
    public function logout() {
        try {
            $this->activitylogger->log('logout', 'user', $this->current_user['user_id'], [
                'ip' => $this->input->ip_address()
            ]);

            $this->_jsonResponse(['message' => 'Logout successful'], 200);
        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== USER CRUD ==========
    
    /**
     * GET /api/users
     * List all users
     */
    public function index() {
        $this->requirePermission('users.view');

        try {
            $search = $this->input->get('search') ?? '';
            $role = $this->input->get('role') ?? '';
            $branch_id = $this->input->get('branch_id') ?? '';
            $status = $this->input->get('status') ?? '';
            $page = (int)($this->input->get('page') ?? 1);
            $limit = (int)($this->input->get('limit') ?? 10);
            $offset = ($page - 1) * $limit;

            $this->db->select('
                users.*, 
                roles.id as role_id,
                roles.name as role_name, 
                roles.description as role_description,
                branches.name as branch_name
            ');
            $this->db->from('users');
            $this->db->join('model_has_roles', 'model_has_roles.model_id = users.id AND model_has_roles.model_type = "App\\\\Models\\\\User"', 'left');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id', 'left');
            $this->db->join('branches', 'branches.id = users.branch_id', 'left');
            $this->db->where('users.deleted_at IS NULL');

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('users.username', $search);
                $this->db->or_like('users.full_name', $search);
                $this->db->or_like('users.email', $search);
                $this->db->group_end();
            }

            if (!empty($role)) {
                $this->db->where('roles.name', $role);
            }

            if (!empty($branch_id)) {
                $this->db->where('users.branch_id', $branch_id);
            }

            if (!empty($status)) {
                $this->db->where('users.status', $status);
            }

            $total_query = clone $this->db;
            $total = $total_query->count_all_results('', FALSE);

            $this->db->limit($limit, $offset);
            $this->db->order_by('users.id', 'DESC');
            $query = $this->db->get();
            $users = $query->result_array();

            // Remove password from response
            foreach ($users as &$user) {
                unset($user['password']);
            }

            $this->_jsonResponse([
                'data' => $users,
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

    /**
     * GET /api/users/{id}
     * Get user by ID
     */
    public function show($id) {
        $this->requirePermission('users.view');

        try {
            $this->db->select('
                users.*, 
                roles.id as role_id,
                roles.name as role_name,
                roles.description as role_description,
                branches.name as branch_name
            ');
            $this->db->from('users');
            $this->db->join('model_has_roles', 'model_has_roles.model_id = users.id AND model_has_roles.model_type = "App\\\\Models\\\\User"', 'left');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id', 'left');
            $this->db->join('branches', 'branches.id = users.branch_id', 'left');
            $this->db->where('users.id', $id);
            $this->db->where('users.deleted_at IS NULL');
            $query = $this->db->get();
            $user = $query->row_array();

            if (!$user) {
                $this->_jsonResponse(['message' => 'User not found'], 404);
            }

            unset($user['password']);

            $this->_jsonResponse(['data' => $user], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/users/create
     * Create new user
     */
    public function create() {
        $this->requirePermission('users.create');

        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['username'])) {
            $this->_jsonResponse(['message' => 'Username is required'], 400);
        }

        if (empty($input['password'])) {
            $this->_jsonResponse(['message' => 'Password is required'], 400);
        }

        if (empty($input['full_name'])) {
            $this->_jsonResponse(['message' => 'Full name is required'], 400);
        }

        // Check username unique
        $this->db->where('username', $input['username']);
        $this->db->where('deleted_at IS NULL');
        $existing = $this->db->get('users');
        
        if ($existing->num_rows() > 0) {
            $this->_jsonResponse(['message' => 'Username already exists'], 400);
        }

        // Check email unique
        if (!empty($input['email'])) {
            $this->db->where('email', $input['email']);
            $this->db->where('deleted_at IS NULL');
            $existing_email = $this->db->get('users');
            
            if ($existing_email->num_rows() > 0) {
                $this->_jsonResponse(['message' => 'Email already exists'], 400);
            }
        }

        try {
            $this->db->trans_start();

            $user_data = [
                'username' => $input['username'],
                'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                'full_name' => $input['full_name'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'branch_id' => $input['branch_id'] ?? null,
                'status' => $input['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('users', $user_data);
            $user_id = $this->db->insert_id();

            // Assign role
            if (!empty($input['role_id'])) {
                $this->db->insert('model_has_roles', [
                    'role_id' => $input['role_id'],
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user_id
                ]);
            }

            // Save password history
            $this->Password_history_model->create([
                'user_id' => $user_id,
                'password_hash' => $user_data['password'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->activitylogger->created('user', $user_id, $user_data);

            $this->_jsonResponse([
                'message' => 'User created successfully',
                'user_id' => $user_id
            ], 201);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/users/update/{id}
     * Update user
     */
    public function update($id) {
        $this->requirePermission('users.edit');

        $input = json_decode(file_get_contents('php://input'), true);

        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $old_user = $this->db->get('users')->row_array();

        if (!$old_user) {
            $this->_jsonResponse(['message' => 'User not found'], 404);
        }

        // Check username unique
        if (!empty($input['username']) && $input['username'] !== $old_user['username']) {
            $this->db->where('username', $input['username']);
            $this->db->where('id !=', $id);
            $this->db->where('deleted_at IS NULL');
            $existing = $this->db->get('users');
            
            if ($existing->num_rows() > 0) {
                $this->_jsonResponse(['message' => 'Username already exists'], 400);
            }
        }

        try {
            $user_data = ['updated_at' => date('Y-m-d H:i:s')];

            if (!empty($input['username'])) $user_data['username'] = $input['username'];
            if (!empty($input['full_name'])) $user_data['full_name'] = $input['full_name'];
            if (!empty($input['email'])) $user_data['email'] = $input['email'];
            if (!empty($input['phone'])) $user_data['phone'] = $input['phone'];
            if (isset($input['branch_id'])) $user_data['branch_id'] = $input['branch_id'];
            if (isset($input['status'])) $user_data['status'] = $input['status'];

            $this->db->where('id', $id);
            $this->db->update('users', $user_data);

            // Update role
            if (!empty($input['role_id'])) {
                $this->db->where('model_id', $id);
                $this->db->where('model_type', 'App\\Models\\User');
                $this->db->delete('model_has_roles');

                $this->db->insert('model_has_roles', [
                    'role_id' => $input['role_id'],
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $id
                ]);
            }

            $this->activitylogger->updated('user', $id, $old_user, $user_data);

            $this->_jsonResponse(['message' => 'User updated successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/users/delete/{id}
     * Soft delete user
     */
    public function delete($id) {
        $this->requirePermission('users.delete');

        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $user = $this->db->get('users')->row_array();

        if (!$user) {
            $this->_jsonResponse(['message' => 'User not found'], 404);
        }

        // Prevent delete super-admin
        $this->db->select('roles.name');
        $this->db->from('model_has_roles');
        $this->db->join('roles', 'roles.id = model_has_roles.role_id');
        $this->db->where('model_has_roles.model_id', $id);
        $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
        $role = $this->db->get()->row_array();

        if ($role && strtolower($role['name']) === 'super-admin') {
            $this->_jsonResponse(['message' => 'Cannot delete super-admin account'], 403);
        }

        try {
            $this->db->where('id', $id);
            $this->db->update('users', ['deleted_at' => date('Y-m-d H:i:s')]);

            $this->activitylogger->deleted('user', $id, $user);

            $this->_jsonResponse(['message' => 'User deleted successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    // ========== PROFILE ==========
    
    /**
     * GET /api/users/profile
     * Get current user profile
     */
    public function profile() {
        try {
            $user_id = $this->current_user['user_id'];

            $this->db->select('users.*, roles.name as role_name, branches.name as branch_name');
            $this->db->from('users');
            $this->db->join('model_has_roles', 'model_has_roles.model_id = users.id AND model_has_roles.model_type = "App\\\\Models\\\\User"', 'left');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id', 'left');
            $this->db->join('branches', 'branches.id = users.branch_id', 'left');
            $this->db->where('users.id', $user_id);
            $query = $this->db->get();
            $user = $query->row_array();

            if (!$user) {
                $this->_jsonResponse(['message' => 'User not found'], 404);
            }

            unset($user['password']);

            $this->_jsonResponse(['data' => $user], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/users/update-profile
     * Update current user profile
     */
    public function update_profile() {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $this->current_user['user_id'];

        try {
            $user_data = ['updated_at' => date('Y-m-d H:i:s')];

            if (!empty($input['full_name'])) $user_data['full_name'] = $input['full_name'];
            if (!empty($input['email'])) $user_data['email'] = $input['email'];
            if (!empty($input['phone'])) $user_data['phone'] = $input['phone'];

            $this->db->where('id', $user_id);
            $this->db->update('users', $user_data);

            $this->activitylogger->log('update_profile', 'user', $user_id, $user_data);

            $this->_jsonResponse(['message' => 'Profile updated successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== PASSWORD MANAGEMENT ==========
    
    /**
     * POST /api/users/change-password
     * Change password (self)
     */
    public function change_password() {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $this->current_user['user_id'];

        if (empty($input['old_password']) || empty($input['new_password'])) {
            $this->_jsonResponse(['message' => 'Old password and new password are required'], 400);
        }

        try {
            $user = $this->User_model->get_by_id($user_id);

            if (!password_verify($input['old_password'], $user['password'])) {
                $this->_jsonResponse(['message' => 'Old password is incorrect'], 400);
            }

            // Check password history (prevent reuse)
            $recent_passwords = $this->Password_history_model->get_recent($user_id, 5);
            foreach ($recent_passwords as $old_pass) {
                if (password_verify($input['new_password'], $old_pass['password_hash'])) {
                    $this->_jsonResponse(['message' => 'Cannot reuse recent passwords'], 400);
                }
            }

            $new_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);

            $this->db->where('id', $user_id);
            $this->db->update('users', [
                'password' => $new_hash,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Save to history
            $this->Password_history_model->create([
                'user_id' => $user_id,
                'password_hash' => $new_hash,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->activitylogger->log('change_password', 'user', $user_id, []);

            $this->_jsonResponse(['message' => 'Password changed successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/users/change-password-admin/{id}
     * Change password (admin only)
     */
    public function change_password_admin($id) {
        $this->check_roles(['admin', 'super-admin']);

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['new_password'])) {
            $this->_jsonResponse(['message' => 'New password is required'], 400);
        }

        try {
            $new_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);

            $this->db->where('id', $id);
            $this->db->update('users', [
                'password' => $new_hash,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Save to history
            $this->Password_history_model->create([
                'user_id' => $id,
                'password_hash' => $new_hash,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->activitylogger->log('admin_change_password', 'user', $id, [
                'admin_id' => $this->current_user['user_id']
            ]);

            $this->_jsonResponse(['message' => 'Password changed successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== SESSION MANAGEMENT ==========
    
    /**
     * GET /api/users/sessions
     * Get active sessions
     */
    public function sessions() {
        try {
            $user_id = $this->current_user['user_id'];

            $this->db->select('*');
            $this->db->from('personal_access_tokens');
            $this->db->where('tokenable_id', $user_id);
            $this->db->where('tokenable_type', 'App\\Models\\User');
            $this->db->where('expires_at >', date('Y-m-d H:i:s'));
            $this->db->order_by('last_used_at', 'DESC');
            $query = $this->db->get();
            $sessions = $query->result_array();

            $this->_jsonResponse(['data' => $sessions], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/users/logout-other-sessions
     * Logout all other sessions
     */
    public function logout_other_sessions() {
        try {
            $user_id = $this->current_user['user_id'];
            $current_token = $this->input->get_request_header('Authorization', TRUE);
            $current_token = str_replace('Bearer ', '', $current_token);

            $this->db->where('tokenable_id', $user_id);
            $this->db->where('tokenable_type', 'App\\Models\\User');
            $this->db->where('token !=', hash('sha256', $current_token));
            $this->db->delete('personal_access_tokens');

            $this->activitylogger->log('logout_other_sessions', 'user', $user_id, []);

            $this->_jsonResponse(['message' => 'Other sessions logged out successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== ROLE & PERMISSION MANAGEMENT ==========
    
    /**
     * POST /api/users/{id}/assign-role
     * Assign role to user
     */
    public function assign_role($user_id) {
        $this->check_roles(['admin', 'super-admin']);

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['role_id'])) {
            $this->_jsonResponse(['message' => 'role_id is required'], 400);
        }

        try {
            // Delete old roles
            $this->db->where('model_id', $user_id);
            $this->db->where('model_type', 'App\\Models\\User');
            $this->db->delete('model_has_roles');

            // Assign new role
            $this->db->insert('model_has_roles', [
                'role_id' => $input['role_id'],
                'model_type' => 'App\\Models\\User',
                'model_id' => $user_id
            ]);

            $this->activitylogger->log('assign_role', 'user', $user_id, [
                'role_id' => $input['role_id'],
                'admin_id' => $this->current_user['user_id']
            ]);

            $this->_jsonResponse(['message' => 'Role assigned successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/users/{id}/permissions
     * Get user permissions
     */
    public function role_permissions($user_id) {
        $this->check_roles(['admin', 'super-admin']);

        try {
            // Get user's role
            $this->db->select('roles.*');
            $this->db->from('model_has_roles');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id');
            $this->db->where('model_has_roles.model_id', $user_id);
            $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
            $role = $this->db->get()->row_array();

            if (!$role) {
                $this->_jsonResponse(['message' => 'User has no role'], 404);
            }

            // Get role's permissions
            $this->db->select('permissions.*');
            $this->db->from('role_has_permissions');
            $this->db->join('permissions', 'permissions.id = role_has_permissions.permission_id');
            $this->db->where('role_has_permissions.role_id', $role['id']);
            $this->db->order_by('permissions.module', 'ASC');
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

    /**
     * POST /api/users/assign-permissions-to-role
     * Assign permissions to role (super-admin only)
     */
    public function assign_permissions_to_role() {
        $this->check_roles(['super-admin']);

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['role_id']) || empty($input['permission_ids'])) {
            $this->_jsonResponse(['message' => 'role_id and permission_ids are required'], 400);
        }

        try {
            $this->db->trans_start();

            // Delete old permissions
            $this->db->where('role_id', $input['role_id']);
            $this->db->delete('role_has_permissions');

            // Assign new permissions
            foreach ($input['permission_ids'] as $permission_id) {
                $this->db->insert('role_has_permissions', [
                    'role_id' => $input['role_id'],
                    'permission_id' => $permission_id
                ]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->activitylogger->log('assign_permissions_to_role', 'role', $input['role_id'], [
                'permission_ids' => $input['permission_ids'],
                'admin_id' => $this->current_user['user_id']
            ]);

            $this->_jsonResponse(['message' => 'Permissions assigned successfully'], 200);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    // ========== ACTIVITY LOGS ==========
    
    /**
     * GET /api/users/{id}/activities
     * Get user activity logs
     */
    public function activities($user_id) {
        $this->check_roles(['admin', 'super-admin']);

        try {
            $page = (int)($this->input->get('page') ?? 1);
            $limit = (int)($this->input->get('limit') ?? 20);
            $offset = ($page - 1) * $limit;

            $this->db->select('*');
            $this->db->from('activity_log');
            $this->db->where('causer_id', $user_id);
            $this->db->where('causer_type', 'App\\Models\\User');
            $this->db->order_by('created_at', 'DESC');
            
            $total_query = clone $this->db;
            $total = $total_query->count_all_results('', FALSE);

            $this->db->limit($limit, $offset);
            $query = $this->db->get();
            $activities = $query->result_array();

            $this->_jsonResponse([
                'data' => $activities,
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

    /**
     * GET /api/users/{id}/login-history
     * Get user login history
     */
    public function login_history($user_id) {
        $this->check_roles(['admin', 'super-admin']);

        try {
            $page = (int)($this->input->get('page') ?? 1);
            $limit = (int)($this->input->get('limit') ?? 20);
            $offset = ($page - 1) * $limit;

            $this->db->select('*');
            $this->db->from('activity_log');
            $this->db->where('causer_id', $user_id);
            $this->db->where('causer_type', 'App\\Models\\User');
            $this->db->where('log_name', 'login');
            $this->db->order_by('created_at', 'DESC');
            
            $total_query = clone $this->db;
            $total = $total_query->count_all_results('', FALSE);

            $this->db->limit($limit, $offset);
            $query = $this->db->get();
            $history = $query->result_array();

            $this->_jsonResponse([
                'data' => $history,
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

    // ========== HELPER APIs ==========
    
    /**
     * GET /api/users/roles
     * Get all roles (for dropdown)
     */
    public function roles() {
        try {
            $this->db->select('id, name, description');
            $this->db->from('roles');
            $this->db->where('deleted_at IS NULL');
            $this->db->order_by('name', 'ASC');
            $query = $this->db->get();
            $roles = $query->result_array();

            $this->_jsonResponse(['data' => $roles], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/users/branches
     * Get all branches (for dropdown)
     */
    public function branches() {
        try {
            $this->db->select('id, code, name');
            $this->db->from('branches');
            $this->db->where('deleted_at IS NULL');
            $this->db->where('status', 'active');
            $this->db->order_by('name', 'ASC');
            $query = $this->db->get();
            $branches = $query->result_array();

            $this->_jsonResponse(['data' => $branches], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== PASSWORD RESET (PUBLIC) ==========
    
    /**
     * POST /api/users/forgot-password
     * Request password reset (public)
     */
    public function forgot_password() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['email'])) {
            $this->_jsonResponse(['message' => 'Email is required'], 400);
        }

        try {
            $this->db->where('email', $input['email']);
            $this->db->where('deleted_at IS NULL');
            $user = $this->db->get('users')->row_array();

            if (!$user) {
                // Don't reveal if email exists
                $this->_jsonResponse(['message' => 'If email exists, reset link has been sent'], 200);
                return;
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->db->insert('password_reset_tokens', [
                'email' => $input['email'],
                'token' => hash('sha256', $token),
                'expires_at' => $expires_at,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // TODO: Send email with reset link
            // $reset_link = base_url("reset-password?token={$token}");

            $this->activitylogger->log('forgot_password', 'user', $user['id'], [
                'ip' => $this->input->ip_address()
            ]);

            $this->_jsonResponse(['message' => 'If email exists, reset link has been sent'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/users/reset-password
     * Reset password with token (public)
     */
    public function reset_password() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['token']) || empty($input['new_password'])) {
            $this->_jsonResponse(['message' => 'Token and new password are required'], 400);
        }

        try {
            $this->db->where('token', hash('sha256', $input['token']));
            $this->db->where('expires_at >', date('Y-m-d H:i:s'));
            $reset = $this->db->get('password_reset_tokens')->row_array();

            if (!$reset) {
                $this->_jsonResponse(['message' => 'Invalid or expired token'], 400);
            }

            // Get user
            $this->db->where('email', $reset['email']);
            $this->db->where('deleted_at IS NULL');
            $user = $this->db->get('users')->row_array();

            if (!$user) {
                $this->_jsonResponse(['message' => 'User not found'], 404);
            }

            // Update password
            $new_hash = password_hash($input['new_password'], PASSWORD_DEFAULT);

            $this->db->where('id', $user['id']);
            $this->db->update('users', [
                'password' => $new_hash,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Save to history
            $this->Password_history_model->create([
                'user_id' => $user['id'],
                'password_hash' => $new_hash,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Delete used token
            $this->db->where('token', hash('sha256', $input['token']));
            $this->db->delete('password_reset_tokens');

            $this->activitylogger->log('reset_password', 'user', $user['id'], [
                'ip' => $this->input->ip_address()
            ]);

            $this->_jsonResponse(['message' => 'Password reset successfully'], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ========== TEST ENDPOINT ==========
    
    /**
     * GET /api/users/test-permissions
     * Test permissions (debug only)
     */
    public function test_permissions() {
        $this->check_roles(['super-admin']);

        try {
            $user_id = $this->current_user['user_id'];

            // Get user's role
            $this->db->select('roles.*');
            $this->db->from('model_has_roles');
            $this->db->join('roles', 'roles.id = model_has_roles.role_id');
            $this->db->where('model_has_roles.model_id', $user_id);
            $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
            $role = $this->db->get()->row_array();

            // Get permissions
            $this->db->select('permissions.*');
            $this->db->from('role_has_permissions');
            $this->db->join('permissions', 'permissions.id = role_has_permissions.permission_id');
            $this->db->where('role_has_permissions.role_id', $role['id']);
            $query = $this->db->get();
            $permissions = $query->result_array();

            // Group by module
            $grouped = [];
            foreach ($permissions as $perm) {
                $module = $perm['module'] ?? 'general';
                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                $grouped[$module][] = $perm['name'];
            }

            $this->_jsonResponse([
                'message' => '✅ Permission Test Results',
                'user' => [
                    'id' => $user_id,
                    'username' => $this->current_user['username'],
                    'role' => $this->current_user['role']
                ],
                'role_details' => $role,
                'permissions_by_module' => $grouped,
                'total_permissions' => count($permissions)
            ], 200);

        } catch (Exception $e) {
            $this->_jsonResponse(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}