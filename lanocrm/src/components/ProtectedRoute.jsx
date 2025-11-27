// src/components/ProtectedRoute.jsx
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useSelector } from 'react-redux';
import '../styles/ProtectedRoute.css';

/**
 * ProtectedRoute - Route bảo mật với permission check
 * @param {ReactNode} children - Component con
 * @param {string|array} requiredPermission - Permission cần có (vd: 'users.view' hoặc ['users.view', 'users.edit'])
 * @param {boolean} requireAuth - Chỉ yêu cầu đăng nhập (không check permission)
 */
const ProtectedRoute = ({ 
  children, 
  requiredPermission = null,
  requireAuth = true 
}) => {
  const location = useLocation();
  const { isAuthenticated, user, permissions } = useSelector(state => state.auth);
  const token = typeof window !== 'undefined' ? localStorage.getItem('lano_token') : null;
  const localUserStr = typeof window !== 'undefined' ? localStorage.getItem('lano_user') : null;
  const localUser = localUserStr ? (() => { try { return JSON.parse(localUserStr); } catch { return null; } })() : null;
  const hasLocalAuth = !!token && !!localUser;
  const effectivePermissions = (permissions && permissions.length) ? permissions : (localUser?.permissions || []);

  // ========== CHECK AUTHENTICATION ==========
  if (requireAuth && !(isAuthenticated || hasLocalAuth)) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // ========== NO PERMISSION REQUIRED ==========
  if (!requiredPermission) {
    return children;
  }

  // ========== SUPER-ADMIN BYPASS ==========
  const isSuperAdmin = user?.role && ['super-admin', 'superadmin', 'admin'].includes(user.role.toLowerCase());
  if (isSuperAdmin) {
    return children;
  }

  // ========== CHECK PERMISSION ==========
  
  /**
   * Helper function to check if user has a specific permission
   * Permissions có thể là array of strings ['users.view'] 
   * hoặc array of objects [{name: 'users.view'}]
   */
  const hasPermission = (perm) => {
    if (!effectivePermissions || !Array.isArray(effectivePermissions)) return false;
    // wildcard
    if (effectivePermissions.includes('*')) return true;
    return effectivePermissions.some(p => {
      // Support cả string và object format
      const permName = typeof p === 'string' ? p : p.name;
      return permName === perm;
    });
  };

  // If requiredPermission là array → Cần ít nhất 1 permission
  if (Array.isArray(requiredPermission)) {
    const hasAnyPermission = requiredPermission.some(hasPermission);
    if (!hasAnyPermission) {
      return <PermissionDenied requiredPermissions={requiredPermission} />;
    }
  } 
  // If requiredPermission là string → Cần permission đó
  else if (typeof requiredPermission === 'string') {
    if (!hasPermission(requiredPermission)) {
      return <PermissionDenied requiredPermissions={[requiredPermission]} />;
    }
  }

  // ========== HAS PERMISSION → ALLOW ACCESS ==========
  return children;
};

/**
 * PermissionDenied component
 */
const PermissionDenied = ({ requiredPermissions }) => {
  const handleGoBack = () => {
    window.history.back();
  };

  return (
    <div className="permission-denied-container">
      <div className="permission-denied-content">
        <div className="permission-denied-icon">
          <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="60" cy="60" r="50" fill="#FEE2E2" />
            <path d="M60 30L60 70" stroke="#DC2626" strokeWidth="6" strokeLinecap="round" />
            <circle cx="60" cy="85" r="5" fill="#DC2626" />
          </svg>
        </div>
        
        <h1>⛔ Truy cập bị từ chối</h1>
        <p className="permission-denied-message">
          Bạn không có quyền truy cập trang này.
        </p>
        
        {requiredPermissions && requiredPermissions.length > 0 && (
          <div className="permission-denied-requirements">
            <p className="permission-label">Quyền cần có:</p>
            <div className="permission-list">
              {requiredPermissions.map((perm, idx) => (
                <span key={idx} className="permission-badge">{perm}</span>
              ))}
            </div>
          </div>
        )}

        <div className="permission-denied-actions">
          <button onClick={handleGoBack} className="btn-back">
            ← Quay lại
          </button>
          <a href="/dashboard" className="btn-home">
            🏠 Trang chủ
          </a>
        </div>
      </div>
    </div>
  );
};

export default ProtectedRoute;
