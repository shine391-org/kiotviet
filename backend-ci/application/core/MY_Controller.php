<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API_Controller Base Class
 * 
 * Base controller cho tất cả API endpoints
 * Tích hợp sẵn JWT authentication, permission checking, CORS, activity logging
 * 
 * FEATURES:
 * - JWT Authentication tự động
 * - Module-based permission checking (mới)
 * - Role-based checking (giữ nguyên cho compatibility)
 * - CORS headers
 * - Activity logging
 * - Public methods whitelist
 * 
 * USAGE:
 * class Products extends API_Controller {
 *     protected $module_name = 'products'; // Set module name
 *     
 *     public function index() {
 *         $this->requirePermission('products.view');
 *         // Your code...
 *     }
 * }
 * 
 * @author CRM Development Team
 * @version 1.0
 * @date 2025-10-26
 */
class MY_Controller extends CI_Controller {
    
    /**
     * Current authenticated user
     * @var array|null
     */
    protected $current_user;
    
    /**
     * Module name (set trong child controller)
     * VD: 'users', 'products', 'orders'
     * @var string|null
     */
    protected $module_name = null;
    
    /**
     * Enable/disable auto JWT validation
     * @var bool
     */
    protected $jwt_enabled = true;
    
    /**
     * Enable/disable auto permission checking
     * @var bool
     */
    protected $permission_check_enabled = false;
    
    /**
     * Constructor
     * - Load common libraries
     * - JWT authentication
     * - Set CORS headers
     */
    public function __construct() {
        parent::__construct();
        
        // Load common libraries
        $this->load->library('JwtAuth');
        $this->load->library('PermissionChecker');
        $this->load->library('ActivityLogger');
        
        // Set CORS headers
        $this->_setCorsHeaders();
        
        // JWT Authentication (nếu không phải public method)
        if ($this->jwt_enabled) {
            $this->_authenticateJwt();
        }
        
        // Set JSON response header
        header('Content-Type: application/json; charset=utf-8');
    }
    
    // ==================== AUTHENTICATION ====================
    
    /**
     * JWT Authentication
     * Tự động bỏ qua các public methods
     */
    private function _authenticateJwt() {
        $method = $this->router->fetch_method();
        $public_methods = $this->getPublicMethods();
        
        // Skip authentication for public methods
        if (in_array($method, $public_methods)) {
            return;
        }
        
        // Validate JWT token
        $this->current_user = $this->jwtauth->validateToken();
        
        if (!$this->current_user) {
            $this->_jsonResponse([
                'message' => 'Unauthorized: Invalid or expired token'
            ], 401);
            exit;
        }
    }
    
    /**
     * Override trong child controller để định nghĩa public methods
     * VD: return ['login', 'register', 'forgot_password'];
     * 
     * @return array
     */
    protected function getPublicMethods() {
        return ['login', 'register', 'forgot_password', 'reset_password'];
    }
    
    // ==================== PERMISSION CHECKING (NEW) ====================
    
    /**
     * Require user có permission cụ thể
     * Throw 403 nếu không có quyền
     * 
     * @param string $permission VD: 'users.view', 'products.create'
     * @return void
     * 
     * @example
     * $this->requirePermission('products.view');
     */
    protected function requirePermission($permission) {
        if (!isset($this->current_user['id'])) {
            $this->_jsonResponse([
                'message' => 'Unauthorized: Not authenticated'
            ], 401);
            exit;
        }
        
        $has_permission = $this->permissionchecker->hasPermission(
            $this->current_user['id'], 
            $permission
        );
        
        if (!$has_permission) {
            // Log failed permission check
            $this->activitylogger->log(
                $this->current_user['id'],
                'permission_denied',
                $this->module_name ?? 'unknown',
                null,
                null,
                null,
                ['required_permission' => $permission]
            );
            
            $this->_jsonResponse([
                'message' => 'Forbidden: You do not have permission to perform this action',
                'required_permission' => $permission
            ], 403);
            exit;
        }
    }
    
    /**
     * Require user có quyền truy cập module
     * 
     * @param string|null $module Module name (default = $this->module_name)
     * @return void
     * 
     * @example
     * $this->requireModuleAccess('products');
     */
    protected function requireModuleAccess($module = null) {
        if (!isset($this->current_user['id'])) {
            $this->_jsonResponse([
                'message' => 'Unauthorized: Not authenticated'
            ], 401);
            exit;
        }
        
        $module = $module ?? $this->module_name;
        
        if (!$module) {
            // Không check nếu không có module name
            return;
        }
        
        $has_access = $this->permissionchecker->hasModuleAccess(
            $this->current_user['id'], 
            $module
        );
        
        if (!$has_access) {
            $this->activitylogger->log(
                $this->current_user['id'],
                'module_access_denied',
                $module,
                null,
                null,
                null,
                ['module' => $module]
            );
            
            $this->_jsonResponse([
                'message' => 'Forbidden: You do not have access to this module',
                'required_module' => $module
            ], 403);
            exit;
        }
    }
    
    /**
     * Check xem user có permission không (không throw error)
     * 
     * @param string $permission
     * @return bool
     */
    protected function hasPermission($permission) {
        if (!isset($this->current_user['id'])) {
            return false;
        }
        
        return $this->permissionchecker->hasPermission(
            $this->current_user['id'], 
            $permission
        );
    }
    
    /**
     * Check xem user có module access không (không throw error)
     * 
     * @param string $module
     * @return bool
     */
    protected function hasModuleAccess($module) {
        if (!isset($this->current_user['id'])) {
            return false;
        }
        
        return $this->permissionchecker->hasModuleAccess(
            $this->current_user['id'], 
            $module
        );
    }
    
    // ==================== ROLE CHECKING (LEGACY - KEEP FOR COMPATIBILITY) ====================
    
    /**
     * Check roles (legacy method - giữ nguyên cho compatibility)
     * 
     * @param array $allowed_roles VD: ['admin', 'super-admin']
     * @return void
     * 
     * @deprecated Use requirePermission() instead
     */
    protected function check_roles($allowed_roles = ['admin']) {
        if (
            !isset($this->current_user['role']) ||
            !in_array(strtolower($this->current_user['role']), array_map('strtolower', $allowed_roles))
        ) {
            $this->_jsonResponse([
                'message' => 'Forbidden: Insufficient permissions',
                'required_roles' => $allowed_roles,
                'your_role' => $this->current_user['role'] ?? 'none'
            ], 403);
            exit;
        }
    }
    
    /**
     * Check if current user is super-admin
     * 
     * @return bool
     */
    protected function isSuperAdmin() {
        return isset($this->current_user['role']) 
            && strtolower($this->current_user['role']) === 'super-admin';
    }
    
    /**
     * Require super-admin role
     */
    protected function requireSuperAdmin() {
        if (!$this->isSuperAdmin()) {
            $this->_jsonResponse([
                'message' => 'Forbidden: Super-admin access required'
            ], 403);
            exit;
        }
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Set CORS headers
     */
    private function _setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Send JSON response và exit
     * 
     * @param array $data
     * @param int $status_code
     */
    protected function _jsonResponse($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Validate required fields trong request data
     * 
     * @param array $data Request data
     * @param array $required_fields Required field names
     * @return array|null Null nếu valid, array errors nếu invalid
     */
    protected function validateRequired($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        return empty($errors) ? null : $errors;
    }
    
    /**
     * Log activity (shorthand)
     * 
     * @param string $action
     * @param string|null $model_type
     * @param int|null $model_id
     * @param array|null $old_values
     * @param array|null $new_values
     */
    protected function logActivity($action, $model_type = null, $model_id = null, $old_values = null, $new_values = null) {
        if (!isset($this->current_user['id'])) {
            return;
        }
        
        $this->activitylogger->log(
            $this->current_user['id'],
            $action,
            $this->module_name ?? 'unknown',
            $model_type,
            $model_id,
            $old_values,
            $new_values
        );
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null
     */
    protected function getCurrentUserId() {
        return $this->current_user['id'] ?? null;
    }
    
    /**
     * Get current user role
     * 
     * @return string|null
     */
    protected function getCurrentUserRole() {
        return $this->current_user['role'] ?? null;
    }
}