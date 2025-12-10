import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { startOfMonth, format } from 'date-fns';
import cashApi from '../../api/cashApi';
import {
  DEFAULT_PAGE_SIZE,
  SUMMARY_SAMPLE_LIMIT,
  today,
} from '../../constants/cash';

const startMonth = format(startOfMonth(new Date()), 'yyyy-MM-dd');

const defaultFilters = {
  search: '',
  type: null,
  category: null,
  branch_id: null,
  payment_method: null,
  status: null,
  reference_type: null,
  date_from: startMonth,
  date_to: today(),
  page: 1,
  limit: DEFAULT_PAGE_SIZE,
};

const aggregateTotals = (items = []) => {
  return items.reduce(
    (acc, tx) => {
      const amount = Number(tx.amount) || 0;
      if (tx.type === 'RECEIPT') acc.receipt += amount;
      if (tx.type === 'PAYMENT') acc.payment += amount;
      return acc;
    },
    { receipt: 0, payment: 0 }
  );
};

export const fetchCashTransactions = createAsyncThunk(
  'cash/fetchTransactions',
  async (params, { rejectWithValue }) => {
    try {
      return await cashApi.getTransactions(params);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchCashTransactionDetail = createAsyncThunk(
  'cash/fetchTransactionDetail',
  async (id, { rejectWithValue }) => {
    try {
      return await cashApi.getTransaction(id);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const createCashReceipt = createAsyncThunk(
  'cash/createReceipt',
  async (payload, { rejectWithValue }) => {
    try {
      return await cashApi.createReceipt(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const createCashPayment = createAsyncThunk(
  'cash/createPayment',
  async (payload, { rejectWithValue }) => {
    try {
      return await cashApi.createPayment(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const deleteCashTransaction = createAsyncThunk(
  'cash/deleteTransaction',
  async (id, { rejectWithValue }) => {
    try {
      await cashApi.deleteTransaction(id);
      return id;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchCashBalance = createAsyncThunk(
  'cash/fetchBalance',
  async ({ branchId = null, filters = {} }, { rejectWithValue }) => {
    try {
      return await cashApi.getBalance(branchId, filters);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchCashSummary = createAsyncThunk(
  'cash/fetchSummary',
  async ({ filters, limit = SUMMARY_SAMPLE_LIMIT }, { rejectWithValue }) => {
    try {
      const response = await cashApi.getTransactions({
        ...filters,
        page: 1,
        limit,
      });

      const totals = aggregateTotals(response.data || []);
      const totalRecords =
        response.pagination?.total ??
        response.total ??
        response.data?.length ??
        0;

      return {
        totals,
        limited: totalRecords > (response.data?.length || 0),
      };
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: DEFAULT_PAGE_SIZE, total: 0, total_pages: 0 },
  filters: defaultFilters,
  current: null,
  loading: false,
  summaryLoading: false,
  creating: false,
  deleting: false,
  balance: null,
  summary: {
    receipt: 0,
    payment: 0,
    net: 0,
    openingBalance: null,
    closingBalance: null,
    limited: false,
  },
  pageTotals: { receipt: 0, payment: 0 },
  error: null,
  createSuccess: false,
};

const cashSlice = createSlice({
  name: 'cash',
  initialState,
  reducers: {
    setCashFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setCashPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    clearCashError(state) {
      state.error = null;
    },
    resetCashState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      // List
      .addCase(fetchCashTransactions.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCashTransactions.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination =
          action.payload.pagination || state.pagination;
        state.pageTotals = aggregateTotals(state.items);
      })
      .addCase(fetchCashTransactions.rejected, (state, action) => {
        state.loading = false;
        const raw = action.payload;
        try {
          const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
          state.error = {
            message: parsed?.message || raw,
            details: parsed?.errors || {},
            type: parsed?.type || 'generic_error',
          };
        } catch {
          state.error = {
            message: raw,
            details: {},
            type: 'generic_error',
          };
        }
      })

      // Detail
      .addCase(fetchCashTransactionDetail.fulfilled, (state, action) => {
        state.current = action.payload.data || null;
      })

      // Create receipt
      .addCase(createCashReceipt.pending, (state) => {
        state.creating = true;
        state.error = null;
        state.createSuccess = false;
      })
      .addCase(createCashReceipt.fulfilled, (state, action) => {
        state.creating = false;
        state.createSuccess = true;
        if (action.payload?.data) {
          state.items = [action.payload.data, ...state.items];
          state.pagination.total += 1;
        }
      })
      .addCase(createCashReceipt.rejected, (state, action) => {
        state.creating = false;
        state.error = action.payload;
      })

      // Create payment
      .addCase(createCashPayment.pending, (state) => {
        state.creating = true;
        state.error = null;
        state.createSuccess = false;
      })
      .addCase(createCashPayment.fulfilled, (state, action) => {
        state.creating = false;
        state.createSuccess = true;
        if (action.payload?.data) {
          state.items = [action.payload.data, ...state.items];
          state.pagination.total += 1;
        }
      })
      .addCase(createCashPayment.rejected, (state, action) => {
        state.creating = false;
        state.error = action.payload;
      })

      // Delete
      .addCase(deleteCashTransaction.pending, (state) => {
        state.deleting = true;
        state.error = null;
      })
      .addCase(deleteCashTransaction.fulfilled, (state, action) => {
        state.deleting = false;
        state.items = state.items.filter((tx) => tx.id !== action.payload);
        state.pagination.total = Math.max(0, state.pagination.total - 1);
      })
      .addCase(deleteCashTransaction.rejected, (state, action) => {
        state.deleting = false;
        state.error = action.payload;
      })

      // Balance
      .addCase(fetchCashBalance.fulfilled, (state, action) => {
        const data = action.payload?.data || {};
        const openingBalance = data.opening_balance ?? null;
        const closingBalance = data.closing_balance ?? data.balance ?? null;

        state.balance = closingBalance;
        if (openingBalance !== null) {
          state.summary.openingBalance = openingBalance;
          state.summary.closingBalance = closingBalance;
        }
      })

      // Summary
      .addCase(fetchCashSummary.pending, (state) => {
        state.summaryLoading = true;
        state.error = null;
      })
      .addCase(fetchCashSummary.fulfilled, (state, action) => {
        state.summaryLoading = false;
        const { receipt, payment } = action.payload.totals;
        const net = receipt - payment;
        state.summary = {
          ...state.summary,
          receipt,
          payment,
          net,
          closingBalance:
            state.summary.openingBalance !== null
              ? state.summary.openingBalance + net
              : null,
          limited: action.payload.limited,
        };
      })
      .addCase(fetchCashSummary.rejected, (state, action) => {
        state.summaryLoading = false;
        state.error = action.payload;
      });
  },
});

export const { setCashFilters, setCashPage, resetCashState, clearCashError } = cashSlice.actions;
export default cashSlice.reducer;
