// src/api/roleApi.js
import axiosInstance from './axios';

const roleApi = {
  /**
   * Lấy danh sách roles
   * GET /api/roles
   */
  getRoles: async (params = {}) => {
    try {
      const response = await axiosInstance.get('/roles', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching roles:', error);
      throw error;
    }
  },

  /**
   * Lấy role by ID
   * GET /api/roles/{id}
   */
  getRoleById: async (id) => {
    try {
      const response = await axiosInstance.get(`/roles/${id}`);
      return response.data.data;
    } catch (error) {
      console.error('Error fetching role:', error);
      throw error;
    }
  },

  /**
   * Tạo role mới
   * POST /api/roles/create
   */
  createRole: async (roleData) => {
    try {
      const response = await axiosInstance.post('/roles/create', roleData);
      return response.data;
    } catch (error) {
      console.error('Error creating role:', error);
      throw error;
    }
  },

  /**
   * Cập nhật role
   * PUT /api/roles/update/{id}
   */
  updateRole: async (id, roleData) => {
    try {
      const response = await axiosInstance.put(`/roles/update/${id}`, roleData);
      return response.data;
    } catch (error) {
      console.error('Error updating role:', error);
      throw error;
    }
  },

  /**
   * Xóa role
   * DELETE /api/roles/delete/{id}
   */
  deleteRole: async (id) => {
    try {
      const response = await axiosInstance.delete(`/roles/delete/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error deleting role:', error);
      throw error;
    }
  },

  // ========== 🆕 PERMISSIONS API ==========

  /**
   * Lấy permissions của 1 role
   * GET /api/roles/{id}/permissions
   */
  getRolePermissions: async (roleId) => {
    try {
      const response = await axiosInstance.get(`/roles/${roleId}/permissions`);
      return response.data;
    } catch (error) {
      console.error('Error fetching role permissions:', error);
      throw error;
    }
  },

  /**
   * Gán permissions cho role
   * POST /api/roles/{id}/assign-permissions
   */
  assignRolePermissions: async (roleId, permissionIds) => {
    try {
      const response = await axiosInstance.post(
        `/roles/${roleId}/assign-permissions`,
        { permission_ids: permissionIds }
      );
      return response.data;
    } catch (error) {
      console.error('Error assigning permissions:', error);
      throw error;
    }
  },

  /**
   * ✅ FIX: Lấy tất cả permissions (grouped by module)
   * GET /api/permissions
   */
  getAllPermissions: async () => {
    try {
      const response = await axiosInstance.get('/permissions');
      const list = response.data.data || [];
      // Group by module for FE accordion
      const grouped = list.reduce((acc, p) => {
        const module = p.module || 'other';
        if (!acc[module]) acc[module] = [];
        acc[module].push(p);
        return acc;
      }, {});
      return grouped;
    } catch (error) {
      console.error('Error fetching all permissions:', error);
      throw error;
    }
  } // ✅ FIX: THÊM DẤU } ĐÃ BỊ THIẾU
};

export default roleApi;
