// src/api/invoiceApi.js
import axiosInstance from './axios';

const invoiceApi = {
  getInvoices: async (params = {}) => {
    const response = await axiosInstance.get('/invoices', { params });
    return response.data;
  },

  getInvoice: async (id) => {
    const response = await axiosInstance.get(`/invoices/${id}`);
    return response.data;
  },
};

export default invoiceApi;
