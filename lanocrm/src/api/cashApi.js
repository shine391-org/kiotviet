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
    if (branchId) params.branch_id = branchId;
    const response = await axiosInstance.get('/cash/balance', { params });
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
