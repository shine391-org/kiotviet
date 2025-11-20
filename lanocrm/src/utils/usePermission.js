/**
 * Custom Hook: usePermission
 * Kiểm tra permissions của user hiện tại
 * 
 * @file src/utils/usePermission.js
 * @author CRM Development Team
 * @date 2025-10-26
 */

import { useSelector } from 'react-redux';

/**
 * Hook để check permissions
 * @returns {Object} - Permission checking functions
 */
export const usePermission = () => {
  const user = useSelector(state => state.auth.user);
  const permissions = useSelector(state => state.auth.permissions);

  /**
   * Check if user is super-admin
   * @returns {boolean}
   */
  const isSuperAdmin = () => {
    if (!user || !user.role) return false;
    const role = user.role.toLowerCase();
    return role === 'super-admin' || role === 'superadmin';
  };

  /**
   * Check if user has a specific permission
   * @param {string} permission - Permission name (e.g., 'users.view')
   * @returns {boolean}
   */
  const hasPermission = (permission) => {
    if (!permission) return true;
    if (isSuperAdmin()) return true;
    return Array.isArray(permissions) && permissions.includes(permission);
  };

  /**
   * Check if user has ANY of the given permissions
   * @param {Array<string>} permissionList - Array of permission names
   * @returns {boolean}
   */
  const hasAnyPermission = (permissionList) => {
    if (!Array.isArray(permissionList) || permissionList.length === 0) return true;
    if (isSuperAdmin()) return true;
    return permissionList.some(perm => permissions.includes(perm));
  };

  /**
   * Check if user has ALL of the given permissions
   * @param {Array<string>} permissionList - Array of permission names
   * @returns {boolean}
   */
  const hasAllPermissions = (permissionList) => {
    if (!Array.isArray(permissionList) || permissionList.length === 0) return true;
    if (isSuperAdmin()) return true;
    return permissionList.every(perm => permissions.includes(perm));
  };

  /**
   * Get user's role
   * @returns {string|null}
   */
  const getUserRole = () => {
    return user?.role || null;
  };

  /**
   * Get all user permissions
   * @returns {Array<string>}
   */
  const getAllPermissions = () => {
    return permissions || [];
  };

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    isSuperAdmin: isSuperAdmin(),
    userRole: getUserRole(),
    permissions: getAllPermissions(),
    user
  };
};

export default usePermission;