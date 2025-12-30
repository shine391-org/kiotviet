/**
 * Report API Service
 * @file src/api/reportApi.js
 * @description API calls for report pages
 */

import axiosInstance from './axios';

const reportApi = {
    /**
     * Get sales report data for chart
     * @param {Object} params - Filter parameters
     * @param {string} params.range - Time range: 'thisMonth', 'lastMonth', 'custom'
     * @param {string} params.branchId - Branch filter
     * @param {string} params.priceListId - Price list filter
     * @param {string} params.salesMethod - Sales method filter
     * @param {string} params.salesChannel - Sales channel filter
     * @param {string} params.startDate - Custom start date (YYYY-MM-DD)
     * @param {string} params.endDate - Custom end date (YYYY-MM-DD)
     * @returns {Promise<Object>}
     */
    getSalesReport: async (params = {}) => {
        const response = await axiosInstance.get('/dashboard/revenue-chart', {
            params: {
                period: 'day',
                range: params.range === 'thisMonth' ? 'month' : params.range === 'lastMonth' ? 'last_month' : params.range,
                branch_id: params.branchId || undefined,
                price_list_id: params.priceListId || undefined,
                sales_method: params.salesMethod || undefined,
                sales_channel: params.salesChannel || undefined,
                start_date: params.startDate || undefined,
                end_date: params.endDate || undefined,
            },
        });
        return response?.data ?? response;
    },

    /**
     * Get branches for filter
     * @returns {Promise<Object>}
     */
    getBranches: async () => {
        const response = await axiosInstance.get('/branches');
        return response.data;
    },

    /**
     * Get price lists for filter
     * @returns {Promise<Object>}
     */
    getPriceLists: async () => {
        const response = await axiosInstance.get('/price-lists');
        return response.data;
    },

    /**
     * Get sales channels for filter
     * @returns {Promise<Object>}
     */
    getSalesChannels: async () => {
        const response = await axiosInstance.get('/sales-channels');
        return response.data;
    },

    /**
     * Get sales report table data with invoices
     * @param {Object} params - Filter parameters
     * @param {string} params.range - Time range: 'month', 'last_month', etc.
     * @param {string} params.branchId - Branch filter
     * @returns {Promise<Object>}
     */
    getSalesReportTable: async (params = {}) => {
        const response = await axiosInstance.get('/dashboard/sales-table', {
            params: {
                range: params.range === 'thisMonth' ? 'month' : params.range === 'lastMonth' ? 'last_month' : params.range,
                branch_id: params.branchId || undefined,
            },
        });
        return response?.data ?? response;
    },
};

export default reportApi;
