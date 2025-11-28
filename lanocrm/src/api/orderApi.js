// src/api/orderApi.js
import axiosInstance from './axios';

const orderApi = {
  getOrders: async (params = {}) => {
    const response = await axiosInstance.get('/orders', { params });
    return response.data;
  },

  getOrder: async (id) => {
    const response = await axiosInstance.get(`/orders/${id}`);
    return response.data;
  },
};

export default orderApi;
