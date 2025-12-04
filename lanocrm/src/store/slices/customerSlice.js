import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import customerApi from '../../api/customerApi';

const defaultPagination = { page: 1, limit: 15, total: 0, total_pages: 0 };

const initialState = {
  items: [],
  pagination: defaultPagination,
  current: null,
  loading: false,
  currentLoading: false,
  saving: false,
  error: null,
  createSuccess: false,
  updateSuccess: false,
};

export const fetchCustomers = createAsyncThunk(
  'customer/fetchAll',
  async (params, { rejectWithValue }) => {
    try {
      return await customerApi.getCustomers(params);
    } catch (err) {
      return rejectWithValue(err.response?.data?.message || err.message);
    }
  }
);

export const fetchCustomer = createAsyncThunk(
  'customer/fetchOne',
  async (id, { rejectWithValue }) => {
    try {
      return await customerApi.getCustomer(id);
    } catch (err) {
      return rejectWithValue(err.response?.data?.message || err.message);
    }
  }
);

export const createCustomer = createAsyncThunk(
  'customer/create',
  async (data, { rejectWithValue }) => {
    try {
      return await customerApi.createCustomer(data);
    } catch (err) {
      return rejectWithValue(err.response?.data?.message || err.message);
    }
  }
);

export const updateCustomer = createAsyncThunk(
  'customer/update',
  async ({ id, data }, { rejectWithValue }) => {
    try {
      return await customerApi.updateCustomer(id, data);
    } catch (err) {
      return rejectWithValue(err.response?.data?.message || err.message);
    }
  }
);

const customerSlice = createSlice({
  name: 'customer',
  initialState,
  reducers: {
    clearCustomerState: () => initialState,
    clearCustomerError: (state) => {
      state.error = null;
    },
    clearCurrentCustomer: (state) => {
      state.current = null;
    }
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchCustomers.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCustomers.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload?.data || [];
        state.pagination = action.payload?.pagination || defaultPagination;
      })
      .addCase(fetchCustomers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })

      .addCase(fetchCustomer.pending, (state) => {
        state.currentLoading = true;
        state.error = null;
      })
      .addCase(fetchCustomer.fulfilled, (state, action) => {
        state.currentLoading = false;
        state.current = action.payload?.data || null;
      })
      .addCase(fetchCustomer.rejected, (state, action) => {
        state.currentLoading = false;
        state.error = action.payload;
      })

      .addCase(createCustomer.pending, (state) => {
        state.saving = true;
        state.createSuccess = false;
        state.error = null;
      })
      .addCase(createCustomer.fulfilled, (state, action) => {
        state.saving = false;
        state.createSuccess = true;
        if (action.payload?.data) {
          state.current = action.payload.data;
        }
      })
      .addCase(createCustomer.rejected, (state, action) => {
        state.saving = false;
        state.error = action.payload;
      })

      .addCase(updateCustomer.pending, (state) => {
        state.saving = true;
        state.updateSuccess = false;
        state.error = null;
      })
      .addCase(updateCustomer.fulfilled, (state, action) => {
        state.saving = false;
        state.updateSuccess = true;
        if (action.payload?.data) {
          state.current = action.payload.data;
        }
      })
      .addCase(updateCustomer.rejected, (state, action) => {
        state.saving = false;
        state.error = action.payload;
      });
  }
});

export const { clearCustomerState, clearCustomerError, clearCurrentCustomer } = customerSlice.actions;
export default customerSlice.reducer;
