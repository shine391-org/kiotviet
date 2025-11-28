// src/api/disposalApi.js
import axiosInstance from './axios';

const disposalApi = {
  getDisposals: async (params = {}) => {
    const response = await axiosInstance.get('/inventory/disposals', { params });
    return response.data;
  },

  getDisposal: async (id) => {
    const response = await axiosInstance.get(`/inventory/disposals/${id}`);
    return response.data;
  },
};

export default disposalApi;
