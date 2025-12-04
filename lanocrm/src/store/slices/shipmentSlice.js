import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format, startOfMonth } from 'date-fns';
import shipmentApi from '../../api/shipmentApi';

const todayIso = () => format(new Date(), 'yyyy-MM-dd');
const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  statuses: [],
  partners: [],
  created_from: startOfMonthIso(),
  created_to: todayIso(),
  created_mode: 'month',
  completed_from: null,
  completed_to: null,
  completed_mode: 'all',
  areas: [],
  cod: 'all',
  sort: 'created_at,desc',
  page: 1,
  limit: 15,
  branch: 'Lano - HN',
  branches: ['Lano - HN'],
};

const aggregatePageTotals = (items = []) => {
  return items.reduce(
    (acc, row) => {
      acc.cod_total += Number(row.cod_amount || 0);
      return acc;
    },
    { cod_total: 0 }
  );
};

export const fetchShipments = createAsyncThunk(
  'shipments/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().shipments.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await shipmentApi.getShipments(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchShipmentDetail = createAsyncThunk(
  'shipments/fetchDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await shipmentApi.getShipment(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  filters: defaultFilters,
  summary: { cod_total: 0 },
  pageTotals: { cod_total: 0 },
  current: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const shipmentSlice = createSlice({
  name: 'shipments',
  initialState,
  reducers: {
    setShipmentFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setShipmentPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearShipmentError(state) {
      state.error = null;
    },
    resetShipmentState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchShipments.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchShipments.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.summary = action.payload.summary || state.summary;
        state.pageTotals = aggregatePageTotals(state.items);
      })
      .addCase(fetchShipments.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchShipmentDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchShipmentDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchShipmentDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setShipmentFilters, setShipmentPage, clearShipmentError, resetShipmentState } =
  shipmentSlice.actions;

export default shipmentSlice.reducer;
