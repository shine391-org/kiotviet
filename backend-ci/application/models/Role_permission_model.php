<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_permission_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Lấy role của user
     * JOIN với bảng model_has_roles
     *
     * @param int $user_id
     * @return array|null
     */
    public function getUserRole($user_id) {
        $this->db->select('roles.id, roles.name, roles.description');
        $this->db->from('model_has_roles');
        $this->db->join('roles', 'roles.id = model_has_roles.role_id', 'inner');
        $this->db->where('model_has_roles.model_id', $user_id);
        $this->db->where('model_has_roles.model_type', 'App\\Models\\User');
        $this->db->limit(1);
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return null;
    }

    /**
     * Lấy tất cả permissions của user
     * Lấy permissions từ role của user
     *
     * @param int $user_id
     * @return array
     */
    public function getUserPermissions($user_id) {
        // Lấy role của user
        $user_role = $this->getUserRole($user_id);
        if (!$user_role) {
            return [];
        }
    
        $role_id = $user_role['id'];
        
        // Lấy permissions của role
        // ✅ MỚI: Thêm module và module_group
        $this->db->select('permissions.id, permissions.name, permissions.display_name, permissions.guard_name, permissions.description, permissions.module, permissions.module_group');
        
        $this->db->from('role_has_permissions');
        $this->db->join('permissions', 'permissions.id = role_has_permissions.permission_id', 'inner');
        $this->db->where('role_has_permissions.role_id', $role_id);
        $this->db->where('permissions.deleted_at IS NULL'); // ✅ THÊM: Filter deleted
        $this->db->order_by('permissions.module_group, permissions.module, permissions.name', 'ASC'); // ✅ SỬA: Order by group
        $query = $this->db->get();
    
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    
        return [];
    }    

    /**
     * Check xem user có permission không
     *
     * @param int $user_id
     * @param string $permission_name
     * @return bool
     */
    public function hasPermission($user_id, $permission_name) {
        $permissions = $this->getUserPermissions($user_id);
        
        foreach ($permissions as $permission) {
            if ($permission['name'] === $permission_name) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Lấy tất cả roles
     *
     * @return array
     */
    public function getAllRoles() {
        $this->db->select('id, name, description, created_at, updated_at');
        $this->db->from('roles');
        $this->db->order_by('name', 'ASC');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        
        return [];
    }

    /**
     * Lấy tất cả permissions
     *
     * @return array
     */
    public function getAllPermissions() {
        $this->db->select('id, name, display_name, description, module, created_at, updated_at');
        $this->db->from('permissions');
        $this->db->order_by('module, name', 'ASC');
        
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        
        return [];
    }

    /**
     * Gán role cho user
     *
     * @param int $user_id
     * @param int $role_id
     * @return bool
     */
    public function assignRoleToUser($user_id, $role_id) {
        // Xóa role cũ
        $this->db->where('model_id', $user_id);
        $this->db->where('model_type', 'App\\Models\\User');
        $this->db->delete('model_has_roles');
        
        // Thêm role mới
        $data = [
            'role_id' => $role_id,
            'model_type' => 'App\\Models\\User',
            'model_id' => $user_id
        ];
        
        return $this->db->insert('model_has_roles', $data);
    }

    /**
     * Xóa role của user
     *
     * @param int $user_id
     * @return bool
     */
    public function removeUserRole($user_id) {
        $this->db->where('model_id', $user_id);
        $this->db->where('model_type', 'App\\Models\\User');
        
        return $this->db->delete('model_has_roles');
    }

    /**
     * Lấy permissions của role
     *
     * @param int $role_id
     * @return array
     */
    public function getRolePermissions($role_id) {
        
        $this->db->select('permissions.id, permissions.name, permissions.display_name, permissions.description, permissions.module, permissions.module_group');
        
        $this->db->from('role_has_permissions');
        $this->db->join('permissions', 'permissions.id = role_has_permissions.permission_id', 'inner');
        $this->db->where('role_has_permissions.role_id', $role_id);
        $this->db->where('permissions.deleted_at IS NULL'); // ✅ THÊM: Filter deleted
        $this->db->order_by('permissions.module_group, permissions.module, permissions.name', 'ASC');
        $query = $this->db->get();
    
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    
        return [];
    }        

    /**
     * Get role by ID
     * @param int $role_id
     * @return array|null
     */
    public function getRoleById($role_id) {
        $this->db->select('*');
        $this->db->from('roles');
        $this->db->where('id', $role_id);
        $this->db->where('deleted_at IS NULL');
        $query = $this->db->get();
        
        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
        
        return null;
    }

    /**
     * Gán permissions cho role
     *
     * @param int $role_id
     * @param array $permission_ids
     * @return bool
     */
    public function syncRolePermissions($role_id, $permission_ids) {
        // Xóa tất cả permissions cũ của role
        $this->db->where('role_id', $role_id);
        $this->db->delete('role_has_permissions');
        
        // Thêm permissions mới
        if (!empty($permission_ids)) {
            $data = [];
            foreach ($permission_ids as $permission_id) {
                $data[] = [
                    'permission_id' => $permission_id,
                    'role_id' => $role_id
                ];
            }
            
            return $this->db->insert_batch('role_has_permissions', $data);
        }
        
        return true;
    }
}