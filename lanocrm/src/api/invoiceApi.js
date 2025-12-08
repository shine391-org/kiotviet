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

  createInvoice: async (data) => {
    const response = await axiosInstance.post('/invoices', data);
    return response.data;
  },

  generateInvoice: async (data) => {
    const response = await axiosInstance.post('/invoices/generate', data);
    return response.data;
  },

  updateInvoice: async (id, data) => {
    const response = await axiosInstance.put(`/invoices/${id}`, data);
    return response.data;
  },

  cancelInvoice: async (id) => {
    const response = await axiosInstance.post(`/invoices/${id}/cancel`);
    return response.data;
  },

  deleteInvoice: async (id) => {
    const response = await axiosInstance.delete(`/invoices/${id}`);
    return response.data;
  },

  generatePdf: async (id) => {
    const response = await axiosInstance.post(`/invoices/${id}/pdf`);
    return response.data;
  },
};

export default invoiceApi;
