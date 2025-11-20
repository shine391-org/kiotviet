<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PermissionChecker Library
 * 
 * Library kiểm tra quyền hạn của user theo module-based RBAC
 * - Check permission: hasPermission($user_id, 'users.view')
 * - Check module access: hasModuleAccess($user_id, 'users')
 * - Cache permissions để tối ưu performance
 * - Clear cache khi role/permissions thay đổi
 * 
 * @author CRM Development Team
 * @version 1.0
 * @date 2025-10-26
 */
class PermissionChecker {
    private $CI;
    private $cache_ttl = 3600; // Cache 1 giờ
    private $cache_enabled = true;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Kiểm tra cache directory
        $cache_dir = APPPATH . 'cache/permissions/';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }
    }

    /**
     * Kiểm tra user có permission cụ thể không
     * 
     * @param int $user_id ID của user
     * @param string $permission_name Tên permission (e.g., "users.view", "products.create")
     * @return bool True nếu có quyền, False nếu không
     * 
     * @example
     * if ($this->permissionchecker->hasPermission($user_id, 'users.create')) {
     *     // Allow create user
     * }
     */
    public function hasPermission($user_id, $permission_name) {
        $permissions = $this->getUserPermissions($user_id);
        return in_array($permission_name, $permissions);
    }

    /**
     * Kiểm tra user có quyền truy cập module không
     * 
     * @param int $user_id ID của user
     * @param string $module_name Tên module (e.g., "users", "products")
     * @return bool True nếu có quyền truy cập module
     * 
     * @example
     * if ($this->permissionchecker->hasModuleAccess($user_id, 'products')) {
     *     // Allow access products module
     * }
     */
    public function hasModuleAccess($user_id, $module_name) {
        $modules = $this->getUserModules($user_id);
        return in_array($module_name, $modules);
    }

    /**
     * Kiểm tra user có BẤT KỲ permission nào trong danh sách
     * 
     * @param int $user_id
     * @param array $permissions Mảng permissions ['users.view', 'users.create']
     * @return bool True nếu có ít nhất 1 permission
     */
    public function hasAnyPermission($user_id, $permissions) {
        $user_permissions = $this->getUserPermissions($user_id);
        foreach ($permissions as $perm) {
            if (in_array($perm, $user_permissions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Kiểm tra user có TẤT CẢ permissions trong danh sách
     * 
     * @param int $user_id
     * @param array $permissions
     * @return bool True nếu có tất cả permissions
     */
    public function hasAllPermissions($user_id, $permissions) {
        $user_permissions = $this->getUserPermissions($user_id);
        foreach ($permissions as $perm) {
            if (!in_array($perm, $user_permissions)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Lấy danh sách TẤT CẢ permissions của user (với caching)
     * 
     * @param int $user_id
     * @return array Mảng permission names ['users.view', 'users.create', ...]
     */
    public function getUserPermissions($user_id) {
        $cache_key = "user_permissions_{$user_id}";
        
        // Thử lấy từ cache
        if ($this->cache_enabled) {
            $cached = $this->getFromCache($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Query từ database
        $this->CI->db->select('permissions.name');
        $this->CI->db->from('model_has_roles');
        $this->CI->db->join('role_has_permissions', 'role_has_permissions.role_id = model_has_roles.role_id');
        $this->CI->db->join('permissions', 'permissions.id = role_has_permissions.permission_id');
        $this->CI->db->where('model_has_roles.model_id', $user_id);
        $this->CI->db->where('model_has_roles.model_type', 'App\\User');
        $this->CI->db->where('permissions.deleted_at IS NULL');
        $this->CI->db->group_by('permissions.name'); // Tránh duplicate
        
        $query = $this->CI->db->get();
        $permissions = array_column($query->result_array(), 'name');

        // Lưu vào cache
        if ($this->cache_enabled) {
            $this->saveToCache($cache_key, $permissions);
        }
        
        return $permissions;
    }

    /**
     * Lấy danh sách MODULES user có quyền truy cập
     * 
     * @param int $user_id
     * @return array Mảng module names ['users', 'products', 'orders', ...]
     */
    public function getUserModules($user_id) {
        $cache_key = "user_modules_{$user_id}";
        
        // Thử lấy từ cache
        if ($this->cache_enabled) {
            $cached = $this->getFromCache($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Query từ database - FIX: Dùng distinct() riêng
        $this->CI->db->distinct();  // ← FIX: Tách distinct ra
        $this->CI->db->select('permissions.module');
        $this->CI->db->from('model_has_roles');
        $this->CI->db->join('role_has_permissions', 'role_has_permissions.role_id = model_has_roles.role_id');
        $this->CI->db->join('permissions', 'permissions.id = role_has_permissions.permission_id');
        $this->CI->db->where('model_has_roles.model_id', $user_id);
        $this->CI->db->where('model_has_roles.model_type', 'App\\User');
        $this->CI->db->where('permissions.deleted_at IS NULL');
        $this->CI->db->where('permissions.module IS NOT NULL');
        $this->CI->db->where('permissions.module !=', '');
        
        $query = $this->CI->db->get();
        $modules = array_column($query->result_array(), 'module');

        // Lưu vào cache
        if ($this->cache_enabled) {
            $this->saveToCache($cache_key, $modules);
        }
        
        return $modules;
    }


    /**
     * Lấy danh sách permissions theo module
     * 
     * @param int $user_id
     * @param string $module_name
     * @return array Mảng permissions thuộc module đó
     */
    public function getUserPermissionsByModule($user_id, $module_name) {
        $all_permissions = $this->getUserPermissions($user_id);
        
        // Filter permissions thuộc module (e.g., "users.view" -> module = "users")
        $module_permissions = [];
        foreach ($all_permissions as $perm) {
            if (strpos($perm, $module_name . '.') === 0) {
                $module_permissions[] = $perm;
            }
        }
        
        return $module_permissions;
    }

    /**
     * Clear cache permissions của 1 user
     * Gọi khi user được gán role mới hoặc role permissions thay đổi
     * 
     * @param int $user_id
     */
    public function clearUserCache($user_id) {
        $this->deleteFromCache("user_permissions_{$user_id}");
        $this->deleteFromCache("user_modules_{$user_id}");
    }

    /**
     * Clear cache của TẤT CẢ users có role_id
     * Gọi khi role permissions thay đổi
     * 
     * @param int $role_id
     */
    public function clearRoleCache($role_id) {
        // Lấy danh sách user_id có role này
        $this->CI->db->select('model_id');
        $this->CI->db->from('model_has_roles');
        $this->CI->db->where('role_id', $role_id);
        $this->CI->db->where('model_type', 'App\\User');
        $query = $this->CI->db->get();
        
        foreach ($query->result_array() as $row) {
            $this->clearUserCache($row['model_id']);
        }
    }

    /**
     * Clear TOÀN BỘ cache permissions
     * Gọi khi cần reset hoàn toàn
     */
    public function clearAllCache() {
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

    /**
     * Enable/disable cache
     * 
     * @param bool $enabled
     */
    public function setCacheEnabled($enabled) {
        $this->cache_enabled = $enabled;
    }

    // ==================== PRIVATE CACHE METHODS ====================

    /**
     * Lấy dữ liệu từ cache (file-based)
     * 
     * @param string $key
     * @return mixed|false False nếu không có hoặc expired
     */
    private function getFromCache($key) {
        $file = APPPATH . 'cache/permissions/' . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return false;
        }
        
        // Check expiry
        if (time() - filemtime($file) > $this->cache_ttl) {
            @unlink($file);
            return false;
        }
        
        $content = @file_get_contents($file);
        if ($content === false) {
            return false;
        }
        
        return unserialize($content);
    }

    /**
     * Lưu dữ liệu vào cache
     * 
     * @param string $key
     * @param mixed $data
     */
    private function saveToCache($key, $data) {
        $cache_dir = APPPATH . 'cache/permissions/';
        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }
        
        $file = $cache_dir . md5($key) . '.cache';
        @file_put_contents($file, serialize($data));
    }

    /**
     * Xóa cache key
     * 
     * @param string $key
     */
    private function deleteFromCache($key) {
        $file = APPPATH . 'cache/permissions/' . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}