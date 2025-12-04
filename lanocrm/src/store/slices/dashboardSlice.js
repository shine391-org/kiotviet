/**
 * Dashboard Redux Slice
 * @file src/store/slices/dashboardSlice.js
 * @description Manages dashboard KPIs, charts, rankings, and activity feed.
 * @agent-layer: state
 * @agent-pattern: Redux Toolkit slice
 * @agent-reusable: MEDIUM
 */

import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import * as dashboardApi from '../../api/dashboardApi';

const extractPayloadData = (payload, fallbackKey) => {
  if (!payload) return null;
  if (payload.data) return payload.data;
  if (fallbackKey && payload[fallbackKey]) return payload[fallbackKey];
  return payload;
};

const getErrorMessage = (error, defaultMessage) =>
  error?.response?.data?.message || error?.message || defaultMessage;

const initialState = {
  kpi: {
    loading: false,
    error: null,
    data: {
      revenue: 0,
      returns: 0,
      netRevenue: 0,
      netChange: 0,
      comparisonLabel: 'so với cùng kỳ tháng trước',
    },
  },
  revenue: {
    loading: false,
    error: null,
    filters: {
      period: 'day', // day | hour | weekday
      range: 'month', // today | week | month | custom
      chartType: 'column', // Default to column chart
      branchId: null,
    },
    data: {
      labels: [],
      values: [],
      branchLabel: 'Lano - HN',
      total: 0,
    },
  },
  topProducts: {
    loading: false,
    error: null,
    filters: {
      metric: 'net_revenue',
      range: 'month',
      limit: 10,
    },
    items: [],
  },
  topCustomers: {
    loading: false,
    error: null,
    filters: {
      range: 'month',
      limit: 10,
    },
    items: [],
  },
  activities: {
    loading: false,
    error: null,
    limit: 15,
    items: [],
  },
  lastUpdated: null,
};

/**
 * Async thunks
 */
export const fetchKpiToday = createAsyncThunk(
  'dashboard/fetchKpiToday',
  async (_, { rejectWithValue }) => {
    try {
      const response = await dashboardApi.getKpiToday();
      return response;
    } catch (error) {
      return rejectWithValue(getErrorMessage(error, 'Không thể tải KPI'));
    }
  }
);

export const fetchRevenueChart = createAsyncThunk(
  'dashboard/fetchRevenueChart',
  async (params = {}, { rejectWithValue }) => {
    try {
      const response = await dashboardApi.getRevenueChart(params);
      return response;
    } catch (error) {
      return rejectWithValue(getErrorMessage(error, 'Không thể tải biểu đồ doanh thu'));
    }
  }
);

export const fetchTopProducts = createAsyncThunk(
  'dashboard/fetchTopProducts',
  async (params = {}, { rejectWithValue }) => {
    try {
      const response = await dashboardApi.getTopProducts(params);
      return response;
    } catch (error) {
      return rejectWithValue(getErrorMessage(error, 'Không thể tải Top hàng bán chạy'));
    }
  }
);

export const fetchTopCustomers = createAsyncThunk(
  'dashboard/fetchTopCustomers',
  async (params = {}, { rejectWithValue }) => {
    try {
      const response = await dashboardApi.getTopCustomers(params);
      return response;
    } catch (error) {
      return rejectWithValue(getErrorMessage(error, 'Không thể tải Top khách mua nhiều'));
    }
  }
);

export const fetchActivities = createAsyncThunk(
  'dashboard/fetchActivities',
  async (params = {}, { rejectWithValue }) => {
    try {
      const response = await dashboardApi.getActivities(params);
      return response;
    } catch (error) {
      return rejectWithValue(getErrorMessage(error, 'Không thể tải hoạt động gần đây'));
    }
  }
);

/**
 * Slice
 */
const dashboardSlice = createSlice({
  name: 'dashboard',
  initialState,
  reducers: {
    setRevenueFilters: (state, action) => {
      state.revenue.filters = { ...state.revenue.filters, ...action.payload };
    },
    setTopProductsFilters: (state, action) => {
      state.topProducts.filters = { ...state.topProducts.filters, ...action.payload };
    },
    setTopCustomersFilters: (state, action) => {
      state.topCustomers.filters = { ...state.topCustomers.filters, ...action.payload };
    },
    setActivitiesLimit: (state, action) => {
      state.activities.limit = action.payload;
    },
    clearDashboardError: (state) => {
      state.kpi.error = null;
      state.revenue.error = null;
      state.topProducts.error = null;
      state.topCustomers.error = null;
      state.activities.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // KPI
      .addCase(fetchKpiToday.pending, (state) => {
        state.kpi.loading = true;
        state.kpi.error = null;
      })
      .addCase(fetchKpiToday.fulfilled, (state, action) => {
        state.kpi.loading = false;
        state.kpi.data = {
          ...state.kpi.data,
          ...extractPayloadData(action.payload),
        };
        state.lastUpdated = new Date().toISOString();
      })
      .addCase(fetchKpiToday.rejected, (state, action) => {
        state.kpi.loading = false;
        state.kpi.error = action.payload;
      })

      // Revenue chart
      .addCase(fetchRevenueChart.pending, (state) => {
        state.revenue.loading = true;
        state.revenue.error = null;
      })
      .addCase(fetchRevenueChart.fulfilled, (state, action) => {
        const chartData = extractPayloadData(action.payload, 'chart') || {};
        state.revenue.loading = false;
        state.revenue.data = {
          labels: chartData.labels || chartData.x || [],
          values: chartData.values || chartData.y || chartData.series || [],
          branchLabel: chartData.branch || chartData.branchLabel || state.revenue.data.branchLabel,
          total: chartData.total ?? chartData.sum ?? state.revenue.data.total,
        };
        state.lastUpdated = new Date().toISOString();
      })
      .addCase(fetchRevenueChart.rejected, (state, action) => {
        state.revenue.loading = false;
        state.revenue.error = action.payload;
      })

      // Top products
      .addCase(fetchTopProducts.pending, (state) => {
        state.topProducts.loading = true;
        state.topProducts.error = null;
      })
      .addCase(fetchTopProducts.fulfilled, (state, action) => {
        state.topProducts.loading = false;
        state.topProducts.items = extractPayloadData(action.payload, 'items') || [];
        state.lastUpdated = new Date().toISOString();
      })
      .addCase(fetchTopProducts.rejected, (state, action) => {
        state.topProducts.loading = false;
        state.topProducts.error = action.payload;
      })

      // Top customers
      .addCase(fetchTopCustomers.pending, (state) => {
        state.topCustomers.loading = true;
        state.topCustomers.error = null;
      })
      .addCase(fetchTopCustomers.fulfilled, (state, action) => {
        state.topCustomers.loading = false;
        state.topCustomers.items = extractPayloadData(action.payload, 'items') || [];
        state.lastUpdated = new Date().toISOString();
      })
      .addCase(fetchTopCustomers.rejected, (state, action) => {
        state.topCustomers.loading = false;
        state.topCustomers.error = action.payload;
      })

      // Activities
      .addCase(fetchActivities.pending, (state) => {
        state.activities.loading = true;
        state.activities.error = null;
      })
      .addCase(fetchActivities.fulfilled, (state, action) => {
        state.activities.loading = false;
        const data = extractPayloadData(action.payload, 'items');
        state.activities.items = Array.isArray(data)
          ? data
          : Array.isArray(data?.items)
            ? data.items
            : data && typeof data === 'object'
              ? Object.values(data)
              : [];
        state.lastUpdated = new Date().toISOString();
      })
      .addCase(fetchActivities.rejected, (state, action) => {
        state.activities.loading = false;
        state.activities.error = action.payload;
      });
  },
});

export const {
  setRevenueFilters,
  setTopProductsFilters,
  setTopCustomersFilters,
  setActivitiesLimit,
  clearDashboardError,
} = dashboardSlice.actions;

export default dashboardSlice.reducer;
