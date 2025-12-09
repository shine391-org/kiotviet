// src/store/slices/purchaseSlice.js
import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import { format } from 'date-fns';
import purchaseApi from '../../api/purchaseApi';
import { startOfMonthIso } from '../../constants/purchases';

const todayIso = () => format(new Date(), 'yyyy-MM-dd');

const defaultFilters = {
    search: '',
    branch_id: null,
    status: null,  // Show all statuses by default
    date_from: null,  // No date filter by default
    date_to: null,
    page: 1,
    limit: 15,
    created_by: null,
    receiver_id: null,
    supplier_id: null,
};

const aggregatePageTotals = (items = []) =>
    items.reduce(
        (acc, row) => {
            acc.total_amount += Number(row.total || 0);
            acc.total_paid += Number(row.paid_amount || 0);
            acc.total_debt += Number(row.debt || 0);
            return acc;
        },
        { total_amount: 0, total_paid: 0, total_debt: 0 }
    );

export const fetchPurchases = createAsyncThunk(
    'purchases/fetchList',
    async (params, { getState, rejectWithValue }) => {
        try {
            const stateFilters = getState().purchases.filters;
            const payload = { ...stateFilters, ...(params || {}) };
            return await purchaseApi.getPurchases(payload);
        } catch (error) {
            return rejectWithValue(error.response?.data?.message || error.message);
        }
    }
);

export const fetchPurchaseDetail = createAsyncThunk(
    'purchases/fetchDetail',
    async (id, { rejectWithValue }) => {
        try {
            return await purchaseApi.getPurchase(id);
        } catch (error) {
            return rejectWithValue(error.response?.data?.message || error.message);
        }
    }
);

const initialState = {
    items: [],
    pagination: { page: 1, limit: defaultFilters.limit, total: 0, total_pages: 0 },
    filters: defaultFilters,
    totals: { total_amount: 0, total_paid: 0, total_debt: 0 },
    pageTotals: { total_amount: 0, total_paid: 0, total_debt: 0 },
    current: null,
    loading: false,
    detailLoading: false,
    error: null,
};

const purchaseSlice = createSlice({
    name: 'purchases',
    initialState,
    reducers: {
        setPurchaseFilters(state, action) {
            state.filters = { ...state.filters, ...action.payload, page: 1 };
        },
        setPurchasePage(state, action) {
            state.filters.page = action.payload.page || 1;
            state.filters.limit = action.payload.limit || state.filters.limit;
        },
        clearPurchaseError(state) {
            state.error = null;
        },
        resetPurchaseState() {
            return initialState;
        },
    },
    extraReducers: (builder) => {
        builder
            .addCase(fetchPurchases.pending, (state) => {
                state.loading = true;
                state.error = null;
            })
            .addCase(fetchPurchases.fulfilled, (state, action) => {
                state.loading = false;
                state.items = action.payload.data || [];
                state.pagination = action.payload.pagination || state.pagination;
                state.totals = action.payload.totals || state.totals;
                state.pageTotals = aggregatePageTotals(state.items);
            })
            .addCase(fetchPurchases.rejected, (state, action) => {
                state.loading = false;
                state.error = action.payload;
            })
            .addCase(fetchPurchaseDetail.pending, (state) => {
                state.detailLoading = true;
                state.error = null;
            })
            .addCase(fetchPurchaseDetail.fulfilled, (state, action) => {
                state.detailLoading = false;
                state.current = action.payload.data || null;
            })
            .addCase(fetchPurchaseDetail.rejected, (state, action) => {
                state.detailLoading = false;
                state.error = action.payload;
            });
    },
});

export const { setPurchaseFilters, setPurchasePage, clearPurchaseError, resetPurchaseState } =
    purchaseSlice.actions;

export default purchaseSlice.reducer;
