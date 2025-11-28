import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format } from 'date-fns';
import disposalApi from '../../api/disposalApi';
import { startOfMonthIso } from '../../constants/disposals';

const todayIso = () => format(new Date(), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  branch_id: null,
  statuses: ['draft', 'completed'],
  date_from: startOfMonthIso(),
  date_to: todayIso(),
  page: 1,
  limit: 15,
  created_by: null,
  executor_id: null,
};

const aggregatePageTotals = (items = []) =>
  items.reduce(
    (acc, row) => {
      acc.total_value += Number(row.total_value || 0);
      acc.total_quantity += Number(row.total_quantity || 0);
      return acc;
    },
    { total_value: 0, total_quantity: 0 }
  );

export const fetchDisposals = createAsyncThunk(
  'disposals/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().disposals.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await disposalApi.getDisposals(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchDisposalDetail = createAsyncThunk(
  'disposals/fetchDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await disposalApi.getDisposal(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  filters: defaultFilters,
  totals: { total_value: 0, total_quantity: 0 },
  pageTotals: { total_value: 0, total_quantity: 0 },
  current: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const disposalSlice = createSlice({
  name: 'disposals',
  initialState,
  reducers: {
    setDisposalFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setDisposalPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearDisposalError(state) {
      state.error = null;
    },
    resetDisposalState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchDisposals.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDisposals.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.totals = action.payload.totals || state.totals;
        state.pageTotals = aggregatePageTotals(state.items);
      })
      .addCase(fetchDisposals.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchDisposalDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchDisposalDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchDisposalDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setDisposalFilters, setDisposalPage, clearDisposalError, resetDisposalState } =
  disposalSlice.actions;

export default disposalSlice.reducer;
