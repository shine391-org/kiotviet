import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import supplierApi from '../../api/supplierApi';

/**
 * Supplier slice
 * @agent-layer: frontend-state
 * @agent-pattern: async-thunk + pagination
 * @agent-reusable: MEDIUM
 */
const defaultPagination = { page: 1, limit: 15, total: 0, total_pages: 0 };

const initialState = {
  items: [],
  pagination: defaultPagination,
  current: null,
  loading: false,
  currentLoading: false,
  saving: false,
  error: null,
};

export const fetchSuppliers = createAsyncThunk(
  'supplier/fetchAll',
  async (params, { rejectWithValue }) => {
    try {
      return await supplierApi.getSuppliers(params);
    } catch (err) {
      return rejectWithValue(err.message || 'Không thể tải nhà cung cấp');
    }
  }
);

export const fetchSupplier = createAsyncThunk(
  'supplier/fetchOne',
  async (id, { rejectWithValue }) => {
    try {
      return await supplierApi.getSupplier(id);
    } catch (err) {
      return rejectWithValue(err.message || 'Không tìm thấy nhà cung cấp');
    }
  }
);

export const createSupplier = createAsyncThunk(
  'supplier/create',
  async (data, { rejectWithValue }) => {
    try {
      return await supplierApi.createSupplier(data);
    } catch (err) {
      return rejectWithValue(err.message || 'Tạo nhà cung cấp thất bại');
    }
  }
);

const supplierSlice = createSlice({
  name: 'supplier',
  initialState,
  reducers: {
    clearSupplierState: () => initialState,
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchSuppliers.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchSuppliers.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload?.data || [];
        state.pagination = action.payload?.pagination || defaultPagination;
      })
      .addCase(fetchSuppliers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })

      .addCase(fetchSupplier.pending, (state) => {
        state.currentLoading = true;
        state.error = null;
      })
      .addCase(fetchSupplier.fulfilled, (state, action) => {
        state.currentLoading = false;
        state.current = action.payload?.data || null;
      })
      .addCase(fetchSupplier.rejected, (state, action) => {
        state.currentLoading = false;
        state.error = action.payload;
      })

      .addCase(createSupplier.pending, (state) => {
        state.saving = true;
        state.error = null;
      })
      .addCase(createSupplier.fulfilled, (state, action) => {
        state.saving = false;
        if (action.payload?.data) {
          state.current = action.payload.data;
        }
      })
      .addCase(createSupplier.rejected, (state, action) => {
        state.saving = false;
        state.error = action.payload;
      });
  },
});

export const { clearSupplierState } = supplierSlice.actions;
export default supplierSlice.reducer;
