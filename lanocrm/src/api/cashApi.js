// src/api/cashApi.js
import axiosInstance from './axios';

const cashApi = {
  getTransactions: async (params = {}) => {
    const response = await axiosInstance.get('/cash/transactions', { params });
    return response.data;
  },

  getTransaction: async (id) => {
    const response = await axiosInstance.get(`/cash/transactions/${id}`);
    return response.data;
  },

  createReceipt: async (data) => {
    const response = await axiosInstance.post('/cash/receipt', data);
    return response.data;
  },

  createPayment: async (data) => {
    const response = await axiosInstance.post('/cash/payment', data);
    return response.data;
  },

  deleteTransaction: async (id) => {
    const response = await axiosInstance.delete(`/cash/transactions/${id}`);
    return response.data;
  },

  getBalance: async (branchId = null, filters = {}) => {
    const params = { ...filters };
    if (branchId) {
      const config = Object.keys(params).length ? { params } : undefined;
      const response = config
        ? await axiosInstance.get(`/cash/balance/branch/${branchId}`, config)
        : await axiosInstance.get(`/cash/balance/branch/${branchId}`);
      return response.data;
    }

    const config = Object.keys(params).length ? { params } : undefined;
    const response = config
      ? await axiosInstance.get('/cash/balance', config)
      : await axiosInstance.get('/cash/balance');
    return response.data;
  },

  getDailyReport: async (date, branchId = null) => {
    const response = await axiosInstance.get('/cash/report/daily', {
      params: { date, branch_id: branchId || undefined },
    });
    return response.data;
  },
};

export default cashApi;
