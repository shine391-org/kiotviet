// src/store/slices/authSlice.js

import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import authApi from '../../api/authApi';

// ==================== ASYNC THUNKS ====================

/**
 * Async thunk cho đăng nhập
 * Sử dụng endpoint mới: POST /api/auth/login
 */
export const loginUser = createAsyncThunk(
  'auth/login',
  async (credentials, { rejectWithValue }) => {
    try {
      const response = await authApi.login(credentials);
      // authApi.login đã xử lý và trả về: { success, token, user }
      if (response.success && response.token && response.user) {
        return {
          success: true,
          token: response.token,
          user: response.user
        };
      } else {
        return rejectWithValue('Đăng nhập thất bại');
      }
    } catch (error) {
      // Xử lý lỗi HTTP
      if (error.response?.data?.message) {
        return rejectWithValue(error.response.data.message);
      } else if (error.response?.status === 401) {
        return rejectWithValue('Tên đăng nhập hoặc mật khẩu không đúng');
      } else if (error.message) {
        return rejectWithValue(error.message);
      } else {
        return rejectWithValue('Không thể kết nối đến server');
      }
    }
  }
);

/**
 * Fetch current user từ backend (với permissions)
 * ❌ KHÔNG SỬ DỤNG SAU KHI LOGIN - CHỈ DÙNG KHI CẦN REFRESH USER DATA
 * GET /api/users/profile
 */
export const fetchCurrentUser = createAsyncThunk(
  'auth/fetchCurrentUser',
  async (_, { rejectWithValue }) => {
    try {
      // ✅ FIX: GỌI authApi.fetchCurrentUser() - SẼ XỬ LÝ BÊN authApi.js
      const user = await authApi.fetchCurrentUser();
      return user;
    } catch (error) {
      if (error.response?.data?.message) {
        return rejectWithValue(error.response.data.message);
      }
      return rejectWithValue('Không thể lấy thông tin người dùng');
    }
  }
);

/**
 * Fetch user permissions
 * GET /api/users/profile
 */
export const fetchUserPermissions = createAsyncThunk(
  'auth/fetchPermissions',
  async (_, { rejectWithValue }) => {
    try {
      const permissions = await authApi.getUserPermissions();
      return permissions;
    } catch (error) {
      if (error.response?.data?.message) {
        return rejectWithValue(error.response.data.message);
      }
      return rejectWithValue('Không thể lấy danh sách quyền');
    }
  }
);

/**
 * Logout user
 * POST /api/users/logout (gọi backend để revoke token)
 */
export const logoutUser = createAsyncThunk(
  'auth/logout',
  async (_, { rejectWithValue }) => {
    try {
      await authApi.logout();
      return null;
    } catch (error) {
      // Vẫn logout dù API fail (đã clear localStorage trong authApi.logout)
      return rejectWithValue(error.message || 'Đăng xuất thất bại');
    }
  }
);

/**
 * Change password
 * POST /api/users/change-password
 */
export const changePassword = createAsyncThunk(
  'auth/changePassword',
  async (passwordData, { rejectWithValue }) => {
    try {
      const response = await authApi.changePassword(passwordData);
      return response;
    } catch (error) {
      if (error.response?.data?.message) {
        return rejectWithValue(error.response.data.message);
      }
      return rejectWithValue('Không thể đổi mật khẩu');
    }
  }
);

// ==================== INITIAL STATE ====================

// Khởi tạo state từ localStorage
const initialState = {
  user: authApi.getUser(),           // ← Đọc từ lano_user
  token: authApi.getToken(),         // ← Đọc từ lano_token
  permissions: authApi.getPermissions(),  // ← Computed từ user.permissions
  isAuthenticated: authApi.isAuthenticated(),
  loading: false,
  error: null,
};
console.log('✅ authSlice initialState:', {
  hasUser: !!initialState.user,
  hasToken: !!initialState.token,
  permissionsCount: initialState.permissions.length,
  isAuthenticated: initialState.isAuthenticated,
});

// ==================== SLICE ====================

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    /**
     * Logout (local only - không gọi API)
     */
    logout: (state) => {
      authApi.clearAuth();
      state.user = null;
      state.token = null;
      state.permissions = [];
      state.isAuthenticated = false;
      state.error = null;
    },

    /**
     * Clear error
     */
    clearError: (state) => {
      state.error = null;
    },

    /**
     * Clear auth state (force logout)
     */
    clearAuth: (state) => {
      authApi.clearAuth();
      state.user = null;
      state.token = null;
      state.permissions = [];
      state.isAuthenticated = false;
      state.error = null;
    },

    /**
     * Update permissions manually (khi role permissions thay đổi)
     */
    updatePermissions: (state, action) => {
      state.permissions = action.payload;
      if (state.user) {
        state.user = {
          ...state.user,
          permissions: action.payload
        };
        authApi.saveUser(state.user);  // ← Lưu lại user với permissions mới
      }
    },

    /**
     * Update user info manually
     */
    updateUser: (state, action) => {
      state.user = { ...state.user, ...action.payload };
      authApi.saveUser(state.user);
    },
  },

  extraReducers: (builder) => {
    // ===== LOGIN =====
    builder
      .addCase(loginUser.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginUser.fulfilled, (state, action) => {
        state.loading = false;
        state.isAuthenticated = true;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.permissions = action.payload.user.permissions || [];
        state.error = null;
      })
      .addCase(loginUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })

      // ===== FETCH CURRENT USER =====
      .addCase(fetchCurrentUser.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchCurrentUser.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload;
        state.permissions = action.payload.permissions || [];
        state.isAuthenticated = true;
      })
      .addCase(fetchCurrentUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
        // ✅ KHÔNG FORCE LOGOUT - CHỈ LOG ERROR
        console.error('fetchCurrentUser failed:', action.payload);
      })

      // ===== FETCH PERMISSIONS =====
      .addCase(fetchUserPermissions.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchUserPermissions.fulfilled, (state, action) => {
        state.loading = false;
        state.permissions = action.payload;
        authApi.savePermissions(action.payload);
      })
      .addCase(fetchUserPermissions.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })

      // ===== LOGOUT =====
      .addCase(logoutUser.pending, (state) => {
        state.loading = true;
      })
      .addCase(logoutUser.fulfilled, (state) => {
        state.loading = false;
        state.user = null;
        state.token = null;
        state.permissions = [];
        state.isAuthenticated = false;
        state.error = null;
      })
      .addCase(logoutUser.rejected, (state) => {
        // Force logout dù API fail (localStorage đã clear)
        state.loading = false;
        state.user = null;
        state.token = null;
        state.permissions = [];
        state.isAuthenticated = false;
      })

      // ===== CHANGE PASSWORD =====
      .addCase(changePassword.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(changePassword.fulfilled, (state) => {
        state.loading = false;
        state.error = null;
      })
      .addCase(changePassword.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

// ==================== SELECTORS ====================

/**
 * Select toàn bộ auth state
 */
export const selectAuth = (state) => state.auth;

/**
 * Select user
 */
export const selectUser = (state) => state.auth.user;

/**
 * Select permissions
 */
export const selectPermissions = (state) => state.auth.permissions;

/**
 * Select isAuthenticated
 */
export const selectIsAuthenticated = (state) => state.auth.isAuthenticated;

/**
 * Select loading
 */
export const selectAuthLoading = (state) => state.auth.loading;

/**
 * Select error
 */
export const selectAuthError = (state) => state.auth.error;

/**
 * Check if user has a specific permission
 * Super-admin bypass
 *
 * Usage: const canView = useSelector(selectHasPermission('users.view'));
 */
export const selectHasPermission = (permission) => (state) => {
  const { user, permissions } = state.auth;

  // Super-admin bypass
  if (user?.role && ['super-admin', 'superadmin'].includes(user.role.toLowerCase())) {
    return true;
  }

  // Check permission
  if (!Array.isArray(permissions)) return false;
  return permissions.some(p =>
    typeof p === 'string' ? p === permission : p.name === permission
  );
};

/**
 * Check if user has ANY of the given permissions
 * Super-admin bypass
 *
 * Usage: const canAccess = useSelector(selectHasAnyPermission(['users.view', 'roles.view']));
 */
export const selectHasAnyPermission = (permissionList) => (state) => {
  const { user, permissions } = state.auth;

  // Super-admin bypass
  if (user?.role && ['super-admin', 'superadmin'].includes(user.role.toLowerCase())) {
    return true;
  }

  // Check if user has at least one permission
  if (!Array.isArray(permissions)) return false;
  return permissionList.some(permission =>
    permissions.some(p =>
      typeof p === 'string' ? p === permission : p.name === permission
    )
  );
};

/**
 * Check if user has ALL of the given permissions
 * Super-admin bypass
 *
 * Usage: const canManage = useSelector(selectHasAllPermissions(['users.edit', 'users.delete']));
 */
export const selectHasAllPermissions = (permissionList) => (state) => {
  const { user, permissions } = state.auth;

  // Super-admin bypass
  if (user?.role && ['super-admin', 'superadmin'].includes(user.role.toLowerCase())) {
    return true;
  }

  // Check if user has all permissions
  if (!Array.isArray(permissions)) return false;
  return permissionList.every(permission =>
    permissions.some(p =>
      typeof p === 'string' ? p === permission : p.name === permission
    )
  );
};

/**
 * Check if user is super-admin
 */
export const selectIsSuperAdmin = (state) => {
  const { user } = state.auth;
  return user?.role && ['super-admin', 'superadmin'].includes(user.role.toLowerCase());
};

/**
 * Get user role
 */
export const selectUserRole = (state) => {
  return state.auth.user?.role || null;
};

/**
 * Get permissions grouped by module
 * Returns: { users: ['users.view', 'users.edit'], roles: [...] }
 */
export const selectPermissionsByModule = (state) => {
  const { permissions } = state.auth;
  if (!Array.isArray(permissions)) return {};

  return permissions.reduce((acc, p) => {
    const permName = typeof p === 'string' ? p : p.name;
    const module = permName.split('.')[0];

    if (!acc[module]) {
      acc[module] = [];
    }

    acc[module].push(permName);
    return acc;
  }, {});
};

// ==================== EXPORTS ====================

export const {
  logout,
  clearError,
  clearAuth,
  updatePermissions,
  updateUser
} = authSlice.actions;

export default authSlice.reducer;