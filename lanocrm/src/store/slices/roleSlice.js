// src/store/slices/roleSlice.js - Redux state cho Roles
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import roleApi from '../../api/roleApi';

// Async thunks
export const fetchRoles = createAsyncThunk(
  'role/fetchRoles',
  async (params, { rejectWithValue }) => {
    try {
      const response = await roleApi.getRoles(params);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tải danh sách vai trò');
    }
  }
);

export const fetchRoleById = createAsyncThunk(
  'role/fetchRoleById',
  async (id, { rejectWithValue }) => {
    try {
      const response = await roleApi.getRoleById(id);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tải thông tin vai trò');
    }
  }
);

export const createRole = createAsyncThunk(
  'role/createRole',
  async (roleData, { rejectWithValue }) => {
    try {
      const response = await roleApi.createRole(roleData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tạo vai trò');
    }
  }
);

export const updateRole = createAsyncThunk(
  'role/updateRole',
  async ({ id, roleData }, { rejectWithValue }) => {
    try {
      const response = await roleApi.updateRole(id, roleData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi cập nhật vai trò');
    }
  }
);

export const deleteRole = createAsyncThunk(
  'role/deleteRole',
  async (id, { rejectWithValue }) => {
    try {
      const response = await roleApi.deleteRole(id);
      return { id, ...response };
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi xóa vai trò');
    }
  }
);

const roleSlice = createSlice({
  name: 'role',
  initialState: {
    roles: [],
    currentRole: null,
    pagination: {
      page: 1,
      limit: 10,
      total: 0,
      total_pages: 0
    },
    loading: false,
    error: null
  },
  reducers: {
    clearCurrentRole: (state) => {
      state.currentRole = null;
    },
    clearError: (state) => {
      state.error = null;
    }
  },
  extraReducers: (builder) => {
    builder
      // Fetch roles
      .addCase(fetchRoles.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchRoles.fulfilled, (state, action) => {
        state.loading = false;
        state.roles = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchRoles.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch role by ID
      .addCase(fetchRoleById.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchRoleById.fulfilled, (state, action) => {
        state.loading = false;
        state.currentRole = action.payload;
      })
      .addCase(fetchRoleById.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Create role
      .addCase(createRole.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(createRole.fulfilled, (state) => {
        state.loading = false;
      })
      .addCase(createRole.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Update role
      .addCase(updateRole.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateRole.fulfilled, (state) => {
        state.loading = false;
      })
      .addCase(updateRole.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Delete role
      .addCase(deleteRole.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(deleteRole.fulfilled, (state, action) => {
        state.loading = false;
        state.roles = state.roles.filter(role => role.id !== action.payload.id);
      })
      .addCase(deleteRole.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  }
});

export const { clearCurrentRole, clearError } = roleSlice.actions;
export default roleSlice.reducer;