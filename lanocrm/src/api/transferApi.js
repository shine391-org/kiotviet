// src/api/transferApi.js
import axiosInstance from './axios';

const transferApi = {
  getTransfers: async (params = {}) => {
    const response = await axiosInstance.get('/inventory/transfers', { params });
    return response.data;
  },

  getTransfer: async (code) => {
    const response = await axiosInstance.get(`/inventory/transfers/${code}`);
    return response.data;
  },

  createTransfer: async (data) => {
    const response = await axiosInstance.post('/inventory/transfers', data);
    return response.data;
  },

  updateTransfer: async (id, data) => {
    const response = await axiosInstance.put(`/inventory/transfers/${id}`, data);
    return response.data;
  },

  submitTransfer: async (id) => {
    const response = await axiosInstance.post(`/inventory/transfers/${id}/submit`);
    return response.data;
  },

  receiveTransfer: async (id, data = {}) => {
    const response = await axiosInstance.post(`/inventory/transfers/${id}/receive`, data);
    return response.data;
  },

  cancelTransfer: async (id) => {
    const response = await axiosInstance.post(`/inventory/transfers/${id}/cancel`);
    return response.data;
  },

  duplicateTransfer: async (code) => {
    const response = await axiosInstance.post(`/inventory/transfers/${code}/duplicate`);
    return response.data;
  },

  openTransfer: async (code) => {
    const response = await axiosInstance.post(`/inventory/transfers/${code}/open`);
    return response.data;
  },

  saveReceivingNotes: async (code, receivingNotes) => {
    const response = await axiosInstance.post(`/inventory/transfers/${code}/notes`, { receivingNotes });
    return response.data;
  },
};

export default transferApi;
