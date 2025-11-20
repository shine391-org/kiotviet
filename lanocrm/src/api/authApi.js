// src/api/authApi.js
import axiosInstance from './axios';

// ==================== CONSTANTS ====================
const TOKEN_KEY = 'lano_token';
const USER_KEY = 'lano_user';

// ==================== AUTHENTICATION API ====================
const authApi = {
  
  // ==================== LOGIN ====================
  login: async (credentials) => {
    try {
      const response = await axiosInstance.post('/auth/login', credentials);
      console.log('✅ Backend response:', response.data);
      
      // Validate response structure
      if (!response.data.token || !response.data.user) {
        throw new Error('Invalid response format from backend');
      }
      
      const { token, user } = response.data;
      
      // ✅ CRITICAL: Save to localStorage
      authApi.saveToken(token);
      authApi.saveUser(user);
      
      console.log('✅ Saved to localStorage:', {
        token: authApi.getToken()?.substring(0, 20) + '...',
        user: authApi.getUser(),
      });
      
      return { success: true, token, user };
      
    } catch (error) {
      console.error('❌ Login error:', error.response?.data || error.message);
      throw error;
    }
  },
  
  // ==================== LOGOUT ====================
  logout: async () => {
    try {
      // Optional: Call backend logout endpoint
      // await axiosInstance.post('/users/logout');
      
      authApi.clearAuth();
      console.log('✅ Logged out successfully');
      
    } catch (error) {
      console.error('❌ Logout error:', error);
      // Clear anyway
      authApi.clearAuth();
    }
  },
  
  // ==================== REFRESH USER DATA ====================
  fetchCurrentUser: async () => {
    try {
      const response = await axiosInstance.get('/users/me');
      const user = response.data.user;
      
      if (user) {
        authApi.saveUser(user);
        console.log('✅ User data refreshed');
        return user;
      }
      
    } catch (error) {
      console.error('❌ Fetch user error:', error);
      throw error;
    }
  },
  
  // ==================== LOCAL STORAGE HELPERS ====================
  
  // Token management
  saveToken: (token) => {
    if (!token) {
      console.error('❌ saveToken: token is empty');
      return;
    }
    localStorage.setItem(TOKEN_KEY, token);
  },
  
  getToken: () => {
    return localStorage.getItem(TOKEN_KEY);
  },
  
  // User management (with permissions)
  saveUser: (user) => {
    if (!user || typeof user !== 'object') {
      console.error('❌ saveUser: invalid user data', user);
      return;
    }
    
    // Validate user structure
    if (!user.id || !user.username) {
      console.warn('⚠️ saveUser: user missing required fields', user);
    }
    
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    console.log('✅ User saved:', {
      id: user.id,
      username: user.username,
      permissions_count: user.permissions?.length || 0,
    });
  },
  
  getUser: () => {
    try {
      const userStr = localStorage.getItem(USER_KEY);
      return userStr ? JSON.parse(userStr) : null;
    } catch (error) {
      console.error('❌ getUser: parse error', error);
      return null;
    }
  },
  
  // Permissions (computed from user)
  getPermissions: () => {
    const user = authApi.getUser();
    return user?.permissions || [];
  },
  
  hasPermission: (permissionName) => {
    const permissions = authApi.getPermissions();
    return permissions.some(p => p.name === permissionName);
  },
  
  // Auth state
  isAuthenticated: () => {
    return !!authApi.getToken() && !!authApi.getUser();
  },
  
  // Clear all auth data
  clearAuth: () => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    
    // ✅ CRITICAL: Clean up old keys (migration)
    localStorage.removeItem('userinfo');
    localStorage.removeItem('permissions');
    localStorage.removeItem('user');
    
    console.log('✅ localStorage cleared');
  },
  
  // ==================== DEBUG HELPERS ====================
  debugAuth: () => {
    console.group('🔍 Auth Debug Info');
    console.log('Token:', authApi.getToken()?.substring(0, 30) + '...');
    console.log('User:', authApi.getUser());
    console.log('Permissions:', authApi.getPermissions());
    console.log('Is Authenticated:', authApi.isAuthenticated());
    console.groupEnd();
  },
};

export default authApi;
