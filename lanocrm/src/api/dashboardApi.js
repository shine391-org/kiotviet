/**
 * Dashboard API service
 * @file src/api/dashboardApi.js
 * @description Centralized endpoints for dashboard data (KPI, charts, rankings, activities)
 * @agent-layer: api
 * @agent-pattern: REST client
 * @agent-reusable: HIGH
 */

import axiosInstance from './axios';

const ENDPOINTS = {
  KPI_TODAY: '/dashboard/kpi-today',
  REVENUE_CHART: '/dashboard/revenue-chart',
  TOP_PRODUCTS: '/dashboard/top-products',
  TOP_CUSTOMERS: '/dashboard/top-customers',
  ACTIVITIES: '/dashboard/activities',
};

const extractData = (response) => response?.data ?? response;

/**
 * Get today's KPI summary
 * @returns {Promise<Object>}
 */
export const getKpiToday = async () => {
  const response = await axiosInstance.get(ENDPOINTS.KPI_TODAY);
  return extractData(response);
};

/**
 * Get revenue chart data
 * @param {Object} params
 * @param {'day'|'hour'|'weekday'} [params.period='day'] - Breakdown dimension
 * @param {'today'|'week'|'month'|'custom'} [params.range='month'] - Time range
 * @param {string|number} [params.branchId] - Branch filter
 * @param {'column'|'bar'} [params.chartType='column'] - Chart style toggle
 * @returns {Promise<Object>}
 */
export const getRevenueChart = async (params = {}) => {
  const {
    period = 'day',
    range = 'month',
    branchId,
    chartType = 'column',
  } = params;

  const response = await axiosInstance.get(ENDPOINTS.REVENUE_CHART, {
    params: {
      period,
      range,
      branch_id: branchId,
      chart_type: chartType,
    },
  });

  return extractData(response);
};

/**
 * Get top products ranking
 * @param {Object} params
 * @param {'net_revenue'|'revenue'|'quantity'|'profit_margin'} [params.metric='net_revenue']
 * @param {'today'|'week'|'month'|'custom'} [params.range='month']
 * @param {number} [params.limit=10]
 * @returns {Promise<Object>}
 */
export const getTopProducts = async (params = {}) => {
  const {
    metric = 'net_revenue',
    range = 'month',
    limit = 10,
  } = params;

  const response = await axiosInstance.get(ENDPOINTS.TOP_PRODUCTS, {
    params: { metric, range, limit },
  });
  return extractData(response);
};

/**
 * Get top customers ranking
 * @param {Object} params
 * @param {'today'|'week'|'month'|'custom'} [params.range='month']
 * @param {number} [params.limit=10]
 * @returns {Promise<Object>}
 */
export const getTopCustomers = async (params = {}) => {
  const {
    range = 'month',
    limit = 10,
  } = params;

  const response = await axiosInstance.get(ENDPOINTS.TOP_CUSTOMERS, {
    params: { range, limit },
  });
  return extractData(response);
};

/**
 * Get activity timeline
 * @param {Object} params
 * @param {number} [params.limit=15]
 * @returns {Promise<Object>}
 */
export const getActivities = async (params = {}) => {
  const { limit = 15 } = params;
  const response = await axiosInstance.get(ENDPOINTS.ACTIVITIES, {
    params: { limit },
  });
  return extractData(response);
};

export default {
  getKpiToday,
  getRevenueChart,
  getTopProducts,
  getTopCustomers,
  getActivities,
};
