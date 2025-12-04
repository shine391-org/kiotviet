// src/api/returnApi.js
import axiosInstance from './axios';

const returnApi = {
  getReturns: async (params = {}) => {
    const response = await axiosInstance.get('/returns', { params });
    return response.data;
  },

  getReturn: async (id) => {
    const response = await axiosInstance.get(`/returns/${id}`);
    return response.data;
  },
};

export default returnApi;
