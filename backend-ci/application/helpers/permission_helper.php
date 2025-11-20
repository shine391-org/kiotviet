<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Helper
 * Helper functions for checking user permissions
 */

/**
 * Check if current user has a specific permission
 * 
 * @param string $permission_name Permission name (e.g., 'users.view')
 * @return bool
 */
if (!function_exists('has_permission')) {
    function has_permission($permission_name)
    {
        $CI =& get_instance();
        
        // Load JwtAuth library nếu chưa load
        if (!isset($CI->jwtauth)) {
            $CI->load->library('JwtAuth');
        }

        // Validate token
        $current_user = $CI->jwtauth->validateToken();
        
        if (!$current_user) {
            return false;
        }

        // Super-admin bypass
        if (isset($current_user['role']) && in_array(strtolower($current_user['role']), ['super-admin', 'superadmin'])) {
            return true;
        }

        // Lấy permissions của user
        $user_id = $current_user['id'];
        
        $CI->db->select('permissions.name');
        $CI->db->from('permissions');
        $CI->db->join('role_has_permissions', 'role_has_permissions.permission_id = permissions.id');
        $CI->db->join('model_has_roles', 'model_has_roles.role_id = role_has_permissions.role_id');
        $CI->db->where('model_has_roles.model_id', $user_id);
        $CI->db->where('model_has_roles.model_type', 'App\\Models\\User');
        $CI->db->where('permissions.name', $permission_name);
        $CI->db->where('permissions.deleted_at IS NULL');
        
        $query = $CI->db->get();
        
        return $query->num_rows() > 0;
    }
}

/**
 * Check if current user has any of the given permissions
 * 
 * @param array $permissions Array of permission names
 * @return bool
 */
if (!function_exists('has_any_permission')) {
    function has_any_permission($permissions)
    {
        foreach ($permissions as $permission) {
            if (has_permission($permission)) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Check if current user has all of the given permissions
 * 
 * @param array $permissions Array of permission names
 * @return bool
 */
if (!function_exists('has_all_permissions')) {
    function has_all_permissions($permissions)
    {
        foreach ($permissions as $permission) {
            if (!has_permission($permission)) {
                return false;
            }
        }
        return true;
    }
}