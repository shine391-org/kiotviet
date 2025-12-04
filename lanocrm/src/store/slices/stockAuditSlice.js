import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import stockAuditApi from '../../api/stockAuditApi';

const defaultFilters = {
  search: '',
  statuses: ['draft', 'balanced'],
  creators: [],
  dateMode: 'this_month',
  customFrom: null,
  customTo: null,
  page: 1,
  limit: 15,
  sort: 'createdTime,desc',
};

export const fetchStockAudits = createAsyncThunk(
  'stockAudits/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().stockAudits.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await stockAuditApi.getAudits(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchStockAuditDetail = createAsyncThunk(
  'stockAudits/fetchDetail',
  async (code, { rejectWithValue }) => {
    try {
      return await stockAuditApi.getAudit(code);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  summary: { totalActualQuantity: 0, totalActualValue: 0, totalDifferenceQty: 0, totalDifferenceValue: 0 },
  filters: defaultFilters,
  loading: false,
  detailLoading: false,
  error: null,
  expandedCode: null,
  details: {},
};

const stockAuditSlice = createSlice({
  name: 'stockAudits',
  initialState,
  reducers: {
    setStockAuditFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setStockAuditPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    setStockAuditExpanded(state, action) {
      state.expandedCode = action.payload;
    },
    resetStockAuditState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchStockAudits.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchStockAudits.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.summary = action.payload.summary || state.summary;
      })
      .addCase(fetchStockAudits.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchStockAuditDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchStockAuditDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        const audit = action.payload?.audit || null;
        if (audit?.code) {
          state.details[audit.code] = audit;
          state.expandedCode = audit.code;
        }
      })
      .addCase(fetchStockAuditDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setStockAuditFilters, setStockAuditPage, resetStockAuditState, setStockAuditExpanded } =
  stockAuditSlice.actions;

export default stockAuditSlice.reducer;
