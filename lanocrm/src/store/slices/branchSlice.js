import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import branchApi from '../../api/branchApi';

// Async thunks
export const fetchBranches = createAsyncThunk(
  'branch/fetchBranches',
  async (params, { rejectWithValue }) => {
    try {
      const response = await branchApi.getBranches(params);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tải danh sách');
    }
  }
);

export const createBranch = createAsyncThunk(
  'branch/createBranch',
  async (branchData, { rejectWithValue }) => {
    try {
      const response = await branchApi.createBranch(branchData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi tạo chi nhánh');
    }
  }
);

export const updateBranch = createAsyncThunk(
  'branch/updateBranch',
  async ({ id, branchData }, { rejectWithValue }) => {
    try {
      const response = await branchApi.updateBranch(id, branchData);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi cập nhật chi nhánh');
    }
  }
);

export const deleteBranch = createAsyncThunk(
  'branch/deleteBranch',
  async (id, { rejectWithValue }) => {
    try {
      const response = await branchApi.deleteBranch(id);
      return { id, ...response };
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || 'Lỗi khi xóa chi nhánh');
    }
  }
);
export const setDefaultBranch = createAsyncThunk(
  'branch/setDefaultBranch',
  async (id, { rejectWithValue }) => {
    try {
      const response = await branchApi.setDefaultBranch(id);
      console.log('✅ Set default success:', response);
      return { id, data: response };
    } catch (error) {
      console.error('❌ Set default failed:', error);
      return rejectWithValue(error || 'Lỗi khi đặt mặc định');
    }
  }
);

const branchSlice = createSlice({
  name: 'branch',
  initialState: {
    branches: [],
    total: 0,
    loading: false,
    error: null,
  },
  reducers: {
    clearError: (state) => {
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchBranches.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchBranches.fulfilled, (state, action) => {
        state.loading = false;
        state.branches = action.payload.data || [];
        state.total = action.payload.total || 0;
      })
      .addCase(fetchBranches.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(createBranch.fulfilled, (state, action) => {
        if (action.payload.data) {
          state.branches.unshift(action.payload.data);
          state.total += 1;
        }
      })
      .addCase(updateBranch.fulfilled, (state, action) => {
        if (action.payload.data) {
          const index = state.branches.findIndex(b => b.id === action.payload.data.id);
          if (index !== -1) {
            state.branches[index] = action.payload.data;
          }
        }
      })
      .addCase(deleteBranch.fulfilled, (state, action) => {
        state.branches = state.branches.filter(b => b.id !== action.payload.id);
        state.total -= 1;
      })
      .addCase(setDefaultBranch.fulfilled, (state, action) => {
        // Bỏ mặc định tất cả chi nhánh
        state.branches = state.branches.map(branch => ({
          ...branch,
          is_default: 0
        }));
        
        // Đặt chi nhánh mới làm mặc định
        const index = state.branches.findIndex(b => b.id === action.payload.id);
          if (index !== -1) {
          state.branches[index] = {
            ...state.branches[index],
            is_default: 1
            };
        }
      }); 
  },
});

export const { clearError } = branchSlice.actions;
export default branchSlice.reducer;