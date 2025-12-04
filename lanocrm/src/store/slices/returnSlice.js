import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format } from 'date-fns';
import returnApi from '../../api/returnApi';
import { startOfMonthIso } from '../../constants/returns';

const todayIso = () => format(new Date(), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  branch_id: null,
  return_types: ['invoice', 'quick', 'exchange'],
  status: ['completed'],
  date_from: startOfMonthIso(),
  date_to: todayIso(),
  page: 1,
  limit: 15,
  created_by: null,
  receiver_id: null,
  channel: null,
  fee_type: null,
};

const aggregatePageTotals = (items = []) =>
  items.reduce(
    (acc, row) => {
      acc.goods_total += Number(row.goods_amount || 0);
      acc.need_refund += Number(row.need_refund || row.customer_refund || 0);
      acc.refunded += Number(row.refunded_amount || 0);
      return acc;
    },
    { goods_total: 0, need_refund: 0, refunded: 0 }
  );

export const fetchReturns = createAsyncThunk(
  'returns/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().returns.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await returnApi.getReturns(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchReturnDetail = createAsyncThunk(
  'returns/fetchDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await returnApi.getReturn(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  filters: defaultFilters,
  totals: { goods_total: 0, need_refund: 0, refunded: 0 },
  pageTotals: { goods_total: 0, need_refund: 0, refunded: 0 },
  current: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const returnSlice = createSlice({
  name: 'returns',
  initialState,
  reducers: {
    setReturnFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setReturnPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearReturnError(state) {
      state.error = null;
    },
    resetReturnState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchReturns.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchReturns.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.totals = action.payload.totals || state.totals;
        state.pageTotals = aggregatePageTotals(state.items);
      })
      .addCase(fetchReturns.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchReturnDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchReturnDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchReturnDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setReturnFilters, setReturnPage, clearReturnError, resetReturnState } =
  returnSlice.actions;

export default returnSlice.reducer;
