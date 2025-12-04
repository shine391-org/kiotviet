import axios from './axios';

const permissionApi = {
  /**
   * Get all permissions (grouped by module)
   * GET /api/permissions
   */
  getAllPermissions: () => {
    return axios.get('/permissions');
  },

  /**
   * Get permissions of a specific role
   * GET /api/roles/{id}/permissions
   */
  getRolePermissions: (roleId) => {
    return axios.get(`/roles/${roleId}/permissions`);
  },

  /**
   * Assign permissions to a role
   * POST /api/roles/{id}/assign-permissions
   */
  assignPermissionsToRole: (roleId, permissionIds) => {
    return axios.post(`/roles/${roleId}/assign-permissions`, {
      permission_ids: permissionIds
    });
  }
};

export default permissionApi;