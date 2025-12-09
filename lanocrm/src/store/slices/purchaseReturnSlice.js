// src/store/slices/purchaseReturnSlice.js
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import purchaseReturnApi from '../../api/purchaseReturnApi';
import { startOfMonthIso } from '../../constants/purchaseReturns';

const initialState = {
    items: [],
    current: null,
    pagination: {
        page: 1,
        limit: 15,
        total: 0,
        total_pages: 0,
    },
    totals: {
        total_amount: 0,
        total_discount: 0,
        total_ncc_can_tra: 0,
        total_ncc_da_tra: 0,
    },
    pageTotals: {
        total_amount: 0,
        total_discount: 0,
        total_ncc_can_tra: 0,
        total_ncc_da_tra: 0,
    },
    filters: {
        page: 1,
        limit: 15,
        status: ['draft', 'returned'],
        date_from: startOfMonthIso(),
        date_to: null,
        time_mode: 'month',
    },
    loading: false,
    detailLoading: false,
    error: null,
};

export const fetchPurchaseReturns = createAsyncThunk(
    'purchaseReturns/fetchPurchaseReturns',
    async (_, { getState, rejectWithValue }) => {
        try {
            const { filters } = getState().purchaseReturns;
            // Convert status array to comma-separated string
            const params = { ...filters };
            if (Array.isArray(params.status)) {
                params.status = params.status.join(',');
            }
            const data = await purchaseReturnApi.getPurchaseReturns(params);
            return data;
        } catch (error) {
            return rejectWithValue(error.response?.data?.message || 'Lỗi tải dữ liệu');
        }
    }
);

export const fetchPurchaseReturnDetail = createAsyncThunk(
    'purchaseReturns/fetchPurchaseReturnDetail',
    async (id, { rejectWithValue }) => {
        try {
            const data = await purchaseReturnApi.getPurchaseReturn(id);
            return data.data;
        } catch (error) {
            return rejectWithValue(error.response?.data?.message || 'Lỗi tải chi tiết');
        }
    }
);

const purchaseReturnSlice = createSlice({
    name: 'purchaseReturns',
    initialState,
    reducers: {
        setPurchaseReturnFilters(state, action) {
            state.filters = { ...state.filters, ...action.payload };
            // Reset to page 1 if filters change (except page itself)
            if (!action.payload.page) {
                state.filters.page = 1;
            }
        },
        resetFilters(state) {
            state.filters = initialState.filters;
        },
        clearCurrent(state) {
            state.current = null;
        },
    },
    extraReducers: (builder) => {
        builder
            .addCase(fetchPurchaseReturns.pending, (state) => {
                state.loading = true;
                state.error = null;
            })
            .addCase(fetchPurchaseReturns.fulfilled, (state, action) => {
                state.loading = false;
                state.items = action.payload.data || [];
                state.pagination = action.payload.pagination || initialState.pagination;
                state.totals = action.payload.totals || initialState.totals;
                state.pageTotals = action.payload.totals || initialState.pageTotals;
            })
            .addCase(fetchPurchaseReturns.rejected, (state, action) => {
                state.loading = false;
                state.error = action.payload;
            })
            .addCase(fetchPurchaseReturnDetail.pending, (state) => {
                state.detailLoading = true;
            })
            .addCase(fetchPurchaseReturnDetail.fulfilled, (state, action) => {
                state.detailLoading = false;
                state.current = action.payload;
            })
            .addCase(fetchPurchaseReturnDetail.rejected, (state) => {
                state.detailLoading = false;
            });
    },
});

export const { setPurchaseReturnFilters, resetFilters, clearCurrent } = purchaseReturnSlice.actions;
export default purchaseReturnSlice.reducer;
