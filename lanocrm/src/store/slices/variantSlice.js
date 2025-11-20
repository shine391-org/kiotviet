import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import productApi from '../../api/productApi';

// Async thunk lấy thuộc tính biến thể và giá trị gán cho biến thể
export const fetchVariantAttributes = createAsyncThunk(
  'variant/fetchAttributes',
  async (variantId, thunkAPI) => {
    try {
      const response = await productApi.getVariantAttributes(variantId);
      return response.data;
    } catch (error) {
      return thunkAPI.rejectWithValue(error.message || 'Lỗi khi tải thuộc tính biến thể');
    }
  }
);

// Async thunk đồng bộ giá trị thuộc tính biến thể
export const syncVariantAttributes = createAsyncThunk(
  'variant/syncAttributes',
  async ({ variantId, attributeValues }, thunkAPI) => {
    try {
      const response = await productApi.syncVariantAttributeValues(variantId, attributeValues);
      return response;
    } catch (error) {
      return thunkAPI.rejectWithValue(error.message || 'Lỗi khi đồng bộ thuộc tính biến thể');
    }
  }
);

// State ban đầu
const initialState = {
  attributes: [],    // Danh sách attributes và options của biến thể
  values: [],        // Giá trị đã gán cho biến thể
  loading: false,
  error: null,
  syncStatus: null,  // 'success' | 'failed' | null
  usedOptionsMap: {},
};

const variantSlice = createSlice({
  name: 'variant',
  initialState,
  reducers: {
    clearSyncStatus(state) {
      state.syncStatus = null;
      state.error = null;
    },
    clearError(state) {
      state.error = null;
    },
    setUsedOptionsMap(state, action) {
      state.usedOptionsMap = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchVariantAttributes.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchVariantAttributes.fulfilled, (state, action) => {
        state.loading = false;
        state.attributes = action.payload.attributes || [];
        state.values = action.payload.values || [];
      })
      .addCase(fetchVariantAttributes.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || action.error.message;
      })
      .addCase(syncVariantAttributes.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.syncStatus = null;
      })
      .addCase(syncVariantAttributes.fulfilled, (state) => {
        state.loading = false;
        state.syncStatus = 'success';
      })
      .addCase(syncVariantAttributes.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || action.error.message;
        state.syncStatus = 'failed';
      });
  }
});

export const { clearSyncStatus, clearError, setUsedOptionsMap } = variantSlice.actions;

export default variantSlice.reducer;