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

  getInvoiceByCode: async (code) => {
    const response = await axiosInstance.get('/invoices', { params: { search: code, limit: 1 } });
    const invoice = response.data?.data?.[0] || null;
    if (!invoice) return { success: false, message: 'Không tìm thấy hóa đơn' };
    // Return search result directly - already contains all needed data
    return { success: true, data: invoice };
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
