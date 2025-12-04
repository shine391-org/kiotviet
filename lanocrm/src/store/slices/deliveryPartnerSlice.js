import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import deliveryPartnerApi from '../../api/deliveryPartnerApi';
import { DELIVERY_PARTNER_PAGE_SIZES, defaultVisibleColumns } from '../../constants/deliveryPartners';

const initialFilters = {
  search: '',
  tab: 'other',
  group: 'all',
  status: 'active',
  fee_from: null,
  fee_to: null,
  debt_from: null,
  debt_to: null,
  date_from: null,
  date_to: null,
  page: 1,
  limit: DELIVERY_PARTNER_PAGE_SIZES[0],
  sort_by: 'code',
  sort_order: 'ascend',
};

export const fetchDeliveryPartners = createAsyncThunk(
  'deliveryPartners/fetchList',
  async (params = {}, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().deliveryPartners.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await deliveryPartnerApi.list(payload);
    } catch (error) {
      return rejectWithValue(error.message || 'Không tải được danh sách đối tác');
    }
  }
);

const deliveryPartnerSlice = createSlice({
  name: 'deliveryPartners',
  initialState: {
    items: [],
    pagination: { page: 1, limit: initialFilters.limit, total: 0, total_pages: 0 },
    filters: { ...initialFilters },
    summary: { total_orders: 0, total_debt: 0, total_fee: 0 },
    visibleColumns: defaultVisibleColumns,
    loading: false,
    error: null,
  },
  reducers: {
    setDeliveryPartnerFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setDeliveryPartnerPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    setDeliveryPartnerSort(state, action) {
      state.filters.sort_by = action.payload.sort_by;
      state.filters.sort_order = action.payload.sort_order;
    },
    setVisibleDeliveryColumns(state, action) {
      state.visibleColumns = action.payload;
    },
    resetDeliveryPartnerState() {
      return {
        items: [],
        pagination: { page: 1, limit: initialFilters.limit, total: 0, total_pages: 0 },
        filters: { ...initialFilters },
        summary: { total_orders: 0, total_debt: 0, total_fee: 0 },
        visibleColumns: defaultVisibleColumns,
        loading: false,
        error: null,
      };
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchDeliveryPartners.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchDeliveryPartners.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.summary = action.payload.summary || state.summary;
      })
      .addCase(fetchDeliveryPartners.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      });
  },
});

export const {
  setDeliveryPartnerFilters,
  setDeliveryPartnerPage,
  setDeliveryPartnerSort,
  setVisibleDeliveryColumns,
  resetDeliveryPartnerState,
} = deliveryPartnerSlice.actions;

export default deliveryPartnerSlice.reducer;
