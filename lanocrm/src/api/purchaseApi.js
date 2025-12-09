// src/api/purchaseApi.js
import api from './axios';

const purchaseApi = {
    /**
     * Get list of purchase orders with filters
     * @param {Object} params - Filter params
     */
    getPurchases: async (params = {}) => {
        const response = await api.get('/purchase-orders', { params });
        return response.data;
    },

    /**
     * Get single purchase order detail
     * @param {number} id - Purchase order ID
     */
    getPurchase: async (id) => {
        const response = await api.get(`/purchase-orders/${id}`);
        return response.data;
    },

    /**
     * Create new purchase order (draft)
     * @param {Object} data - Purchase order data
     */
    createPurchase: async (data) => {
        const response = await api.post('/purchase-orders', data);
        return response.data;
    },

    /**
     * Submit purchase order
     * @param {number} id - Purchase order ID
     */
    submitPurchase: async (id) => {
        const response = await api.post(`/purchase-orders/${id}/submit`);
        return response.data;
    },

    /**
     * Cancel purchase order
     * @param {number} id - Purchase order ID
     */
    cancelPurchase: async (id) => {
        const response = await api.post(`/purchase-orders/${id}/cancel`);
        return response.data;
    },

    /**
     * Export purchase orders to Excel
     * @param {Object} params - Filter params
     */
    exportPurchases: async (params = {}) => {
        const response = await api.get('/purchase-orders/export', {
            params,
            responseType: 'blob',
        });
        return response;
    },
};

export default purchaseApi;

