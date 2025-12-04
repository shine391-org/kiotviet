import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format } from 'date-fns';
import orderApi from '../../api/orderApi';
import { startOfMonthIso } from '../../constants/orders';

const today = () => format(new Date(), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  branch_id: null,
  status: ['draft', 'shipping', 'completed'],
  payment_method: null,
  date_from: startOfMonthIso(),
  date_to: today(),
  page: 1,
  limit: 15,
  shipping_partner: null,
  created_by: null,
  receiver_id: null,
  channel: null,
};

const aggregatePageTotals = (items = []) => {
  return items.reduce(
    (acc, o) => {
      acc.total += Number(o.total || 0);
      acc.paid += Number(o.paid_amount || 0);
      acc.debt += Number(o.debt_amount || 0);
      return acc;
    },
    { total: 0, paid: 0, debt: 0 }
  );
};

export const fetchOrders = createAsyncThunk(
  'orders/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().orders.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await orderApi.getOrders(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchOrderDetail = createAsyncThunk(
  'orders/fetchDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await orderApi.getOrder(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  filters: defaultFilters,
  totals: { total_amount: 0, paid_amount: 0, debt_amount: 0 },
  pageTotals: { total: 0, paid: 0, debt: 0 },
  current: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const orderSlice = createSlice({
  name: 'orders',
  initialState,
  reducers: {
    setOrderFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setOrderPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearOrderError(state) {
      state.error = null;
    },
    resetOrderState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchOrders.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrders.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.totals = action.payload.totals || state.totals;
        state.pageTotals = aggregatePageTotals(state.items);
      })
      .addCase(fetchOrders.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchOrderDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchOrderDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchOrderDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setOrderFilters, setOrderPage, resetOrderState, clearOrderError } = orderSlice.actions;
export default orderSlice.reducer;
