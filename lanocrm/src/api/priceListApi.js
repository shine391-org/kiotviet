import axiosInstance from './axios';

const BASE = '/price-lists';

const priceListApi = {
  getPriceLists: async (params = {}) => {
    const response = await axiosInstance.get(BASE, { params });
    return response.data;
  },

  getPriceList: async (id) => {
    const response = await axiosInstance.get(`${BASE}/${id}`);
    return response.data;
  },

  createPriceList: async (data) => {
    const response = await axiosInstance.post(BASE, data);
    return response.data;
  },

  updatePriceList: async (id, data) => {
    const response = await axiosInstance.put(`${BASE}/${id}`, data);
    return response.data;
  },

  deletePriceList: async (id) => {
    const response = await axiosInstance.delete(`${BASE}/${id}`);
    return response.data;
  },

  getItems: async (id) => {
    const response = await axiosInstance.get(`${BASE}/${id}/items`);
    return response.data;
  },

  saveItems: async (id, items) => {
    const response = await axiosInstance.post(`${BASE}/${id}/items`, { items });
    return response.data;
  },

  previewOrder: async (payload) => {
    const response = await axiosInstance.post('/orders/calculate-preview', payload);
    return response.data;
  },
};

export default priceListApi;
