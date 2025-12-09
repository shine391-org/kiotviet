// src/api/purchaseReturnApi.js
import api from './axios';

const purchaseReturnApi = {
    /**
     * Get list of purchase returns with filters
     * @param {Object} params - Filter params
     */
    getPurchaseReturns: async (params = {}) => {
        const response = await api.get('/purchase-returns', { params });
        return response.data;
    },

    /**
     * Get single purchase return detail
     * @param {number} id - Purchase return ID
     */
    getPurchaseReturn: async (id) => {
        const response = await api.get(`/purchase-returns/${id}`);
        return response.data;
    },

    /**
     * Create new purchase return
     * @param {Object} data - Purchase return data
     */
    createPurchaseReturn: async (data) => {
        const response = await api.post('/purchase-returns', data);
        return response.data;
    },

    /**
     * Update purchase return
     * @param {number} id - Purchase return ID
     * @param {Object} data - Purchase return data
     */
    updatePurchaseReturn: async (id, data) => {
        const response = await api.put(`/purchase-returns/${id}`, data);
        return response.data;
    },

    /**
     * Delete purchase return
     * @param {number} id - Purchase return ID
     */
    deletePurchaseReturn: async (id) => {
        const response = await api.delete(`/purchase-returns/${id}`);
        return response.data;
    },

    /**
     * Update purchase return status
     * @param {number} id - Purchase return ID
     * @param {string} status - New status
     */
    updateStatus: async (id, status) => {
        const response = await api.post(`/purchase-returns/${id}/status`, { status });
        return response.data;
    },

    /**
     * Export purchase returns to Excel
     * @param {Object} params - Filter params
     */
    exportPurchaseReturns: async (params = {}) => {
        const response = await api.get('/purchase-returns/export', {
            params,
            responseType: 'blob',
        });
        return response;
    },
};

export default purchaseReturnApi;
