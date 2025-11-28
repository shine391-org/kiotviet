import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import transferApi from '../../api/transferApi';
import { TRANSFER_STATUSES } from '../../constants/transfers';

const defaultFilters = {
  search: '',
  fromBranches: [],
  toBranches: [],
  statuses: TRANSFER_STATUSES.map((s) => s.value),
  transferDateEnabled: false,
  receiveDateEnabled: false,
  dateMode: 'this_year',
  customFrom: null,
  customTo: null,
  receivingStatus: 'all',
  page: 1,
  limit: 15,
  sort: 'transferDate,desc',
};

export const fetchTransfers = createAsyncThunk(
  'transfers/fetchList',
  async (params, { getState, rejectWithValue }) => {
    try {
      const stateFilters = getState().transfers.filters;
      const payload = { ...stateFilters, ...(params || {}) };
      return await transferApi.getTransfers(payload);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchTransferDetail = createAsyncThunk(
  'transfers/fetchDetail',
  async (code, { rejectWithValue }) => {
    try {
      return await transferApi.getTransfer(code);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const duplicateTransfer = createAsyncThunk(
  'transfers/duplicate',
  async (code, { rejectWithValue }) => {
    try {
      return await transferApi.duplicateTransfer(code);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const openTransfer = createAsyncThunk(
  'transfers/open',
  async (code, { rejectWithValue }) => {
    try {
      return await transferApi.openTransfer(code);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const saveReceivingNotes = createAsyncThunk(
  'transfers/saveNotes',
  async ({ code, receivingNotes }, { rejectWithValue }) => {
    try {
      return await transferApi.saveReceivingNotes(code, receivingNotes);
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

const initialState = {
  items: [],
  pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
  summary: { totalQtySent: 0, totalValueSent: 0, totalQtyReceived: 0, totalValueReceived: 0, totalItems: 0 },
  filters: defaultFilters,
  loading: false,
  detailLoading: false,
  error: null,
  expandedCode: null,
  details: {},
};

const transferSlice = createSlice({
  name: 'transfers',
  initialState,
  reducers: {
    setTransferFilters(state, action) {
      state.filters = { ...state.filters, ...action.payload, page: 1 };
    },
    setTransferPage(state, action) {
      state.filters.page = action.payload.page || 1;
      state.filters.limit = action.payload.limit || state.filters.limit;
    },
    setExpandedCode(state, action) {
      state.expandedCode = action.payload;
    },
    resetTransferState() {
      return initialState;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchTransfers.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchTransfers.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.data || [];
        state.pagination = action.payload.pagination || state.pagination;
        state.summary = action.payload.summary || state.summary;
      })
      .addCase(fetchTransfers.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      .addCase(fetchTransferDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchTransferDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        const transfer = action.payload?.transfer || null;
        if (transfer?.code) {
          state.details[transfer.code] = transfer;
          state.expandedCode = transfer.code;
        }
      })
      .addCase(fetchTransferDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload;
      })
      .addCase(duplicateTransfer.fulfilled, (state, action) => {
        const transfer = action.payload?.transfer;
        if (transfer) {
          state.items = [transfer, ...state.items];
          state.pagination.total = (state.pagination.total || 0) + 1;
          state.details[transfer.code] = { ...transferDetailsFallback(transfer.code), ...transfer };
          state.expandedCode = transfer.code;
        }
      })
      .addCase(openTransfer.fulfilled, (state, action) => {
        const transfer = action.payload?.transfer;
        if (transfer) {
          state.items = state.items.map((item) =>
            item.code === transfer.code ? { ...item, status: transfer.status } : item
          );
          if (state.details[transfer.code]) {
            state.details[transfer.code] = { ...state.details[transfer.code], status: transfer.status };
          }
        }
      })
      .addCase(saveReceivingNotes.fulfilled, (state, action) => {
        const { code, receivingNotes } = action.meta.arg;
        if (state.details[code]) {
          state.details[code] = { ...state.details[code], receivingNotes };
        }
        state.items = state.items.map((item) =>
          item.code === code ? { ...item, notes: receivingNotes } : item
        );
      });
  },
});

function transferDetailsFallback(code) {
  return { code, items: [], summary: { totalItems: 0, totalQtySent: 0, totalValueSent: 0 } };
}

export const { setTransferFilters, setTransferPage, resetTransferState, setExpandedCode } =
  transferSlice.actions;

export default transferSlice.reducer;
