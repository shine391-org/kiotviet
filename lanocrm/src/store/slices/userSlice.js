// src/store/slices/userSlice.js - FIX HOÀN CHỈNH
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import userApi from '../../api/userApi';

// ========== ASYNC THUNKS ==========
export const fetchUsers = createAsyncThunk(
  'user/fetchUsers',
  async (params, { rejectWithValue }) => {
    try {
      const response = await userApi.getUsers(params);
      // ✅ FIX: Backend trả về { data: [], pagination: {} }
      return response; // Trả về toàn bộ response object
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tải danh sách');
    }
  }
);

export const fetchUserById = createAsyncThunk(
  'user/fetchUserById',
  async (id, { rejectWithValue }) => {
    try {
      const response = await userApi.getUserById(id);
      return response.data || response; // Có thể là response.data hoặc response trực tiếp
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tải user');
    }
  }
);

export const createUser = createAsyncThunk(
  'user/createUser',
  async (userData, { rejectWithValue }) => {
    try {
      const response = await userApi.createUser(userData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tạo user');
    }
  }
);

export const updateUser = createAsyncThunk(
  'user/updateUser',
  async ({ id, userData }, { rejectWithValue }) => {
    try {
      const response = await userApi.updateUser(id, userData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi cập nhật user');
    }
  }
);

export const deleteUser = createAsyncThunk(
  'user/deleteUser',
  async (id, { rejectWithValue }) => {
    try {
      const response = await userApi.deleteUser(id);
      return { id, ...response };
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi xóa user');
    }
  }
);

// ========== SLICE ==========
const userSlice = createSlice({
  name: 'user',
  initialState: {
    users: [],
    currentUser: null,
    pagination: {
      page: 1,
      limit: 10,
      total: 0,
      total_pages: 0
    },
    loading: false,
    error: null,
  },
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
    clearCurrentUser: (state) => {
      state.currentUser = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // fetchUsers
      .addCase(fetchUsers.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchUsers.fulfilled, (state, action) => {
        state.loading = false;
        // ✅ FIX: Parse đúng structure từ backend
        state.users = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
      })
      .addCase(fetchUsers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // fetchUserById
      .addCase(fetchUserById.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchUserById.fulfilled, (state, action) => {
        state.loading = false;
        state.currentUser = action.payload;
      })
      .addCase(fetchUserById.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // createUser
      .addCase(createUser.pending, (state) => {
        state.loading = true;
      })
      .addCase(createUser.fulfilled, (state) => {
        state.loading = false;
        // Reload lại danh sách sau khi tạo thành công
      })
      .addCase(createUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // updateUser
      .addCase(updateUser.pending, (state) => {
        state.loading = true;
      })
      .addCase(updateUser.fulfilled, (state) => {
        state.loading = false;
        // Reload lại danh sách sau khi update thành công
      })
      .addCase(updateUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      
      // deleteUser
      .addCase(deleteUser.pending, (state) => {
        state.loading = true;
      })
      .addCase(deleteUser.fulfilled, (state, action) => {
        state.loading = false;
        state.users = state.users.filter(u => String(u.id) !== String(action.payload.id));
        if (state.pagination.total > 0) {
          state.pagination.total -= 1;
        }
      })
      .addCase(deleteUser.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const { clearError, clearCurrentUser } = userSlice.actions;
export default userSlice.reducer;