import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format } from 'date-fns';
import invoiceApi from '../../api/invoiceApi';
import { startOfMonthIso } from '../../constants/invoices';

const todayIso = () => format(new Date(), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  branch_id: null,
  invoice_types: ['standard', 'pickup', 'delivery'],
  invoice_status: ['processing', 'completed'],
  e_invoice_status: null,
  delivery_status: null,
  shipping_partner: null,
  shipping_time_mode: 'all',
  payment_method: null,
  created_by: null,
  seller_id: null,
  price_book_id: null,
  channel: null,
  date_from: startOfMonthIso(),
  date_to: todayIso(),
  page: 1,
  limit: 15,
};

const aggregatePageTotals = (items = []) =>
  items.reduce(
    (acc, row) => {
      acc.customer_payable += Number(row.customer_payable || 0);
      acc.customer_paid += Number(row.customer_paid || 0);
      acc.cod_amount += Number(row.cod_amount || 0);
      acc.shipping_fee += Number(row.shipping_fee || 0);
      return acc;
    },
    { customer_payable: 0, customer_paid: 0, cod_amount: 0, shipping_fee: 0 }
  );

export const fetchInvoices = createAsyncThunk(
  'invoices/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().invoices.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await invoiceApi.getInvoices(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchInvoiceDetail = createAsyncThunk(
  'invoices/fetchDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await invoiceApi.getInvoice(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  filters: defaultFilters,
  totals: {
    customer_payable: 0,
    customer_paid: 0,
    cod_amount: 0,
    shipping_fee: 0,
  },
  pageTotals: {
    customer_payable: 0,
    customer_paid: 0,
    cod_amount: 0,
    shipping_fee: 0,
  },
  current: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const invoiceSlice = createSlice({
  name: 'invoices',
  initialState,
  reducers: {
    setInvoiceFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setInvoicePage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearInvoiceError(state) {
      state.error = null;
    },
    resetInvoiceState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchInvoices.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchInvoices.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.totals = action.payload.totals || state.totals;
        state.pageTotals = aggregatePageTotals(state.items);
      })
      .addCase(fetchInvoices.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchInvoiceDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchInvoiceDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.current = action.payload.data || null;
      })
      .addCase(fetchInvoiceDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setInvoiceFilters, setInvoicePage, clearInvoiceError, resetInvoiceState } =
  invoiceSlice.actions;

export default invoiceSlice.reducer;
