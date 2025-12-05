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

  applyFormula: async (id, payload) => {
    const response = await axiosInstance.post(`${BASE}/${id}/apply-formula`, payload);
    return response.data;
  },

  previewOrder: async (payload) => {
    const response = await axiosInstance.post('/orders/calculate-preview', payload);
    return response.data;
  },

  // Get product price with specific price list applied
  getProductPriceByList: async (priceListId, productId, variantId = null) => {
    const response = await axiosInstance.get(`/products/${productId}/price`, {
      params: { price_list_id: priceListId, variant_id: variantId },
    });
    return response.data;
  },

  // Get products with prices from a specific price list
  getProductsWithPrices: async (priceListId, filters = {}) => {
    const response = await axiosInstance.get('/products', {
      params: { ...filters, with_price_list_id: priceListId },
    });
    return response.data;
  },

  // Add items to price list (append, not replace)
  addItems: async (id, items) => {
    const response = await axiosInstance.post(`${BASE}/${id}/add-items`, { items });
    return response.data;
  },

  // Export price list items to CSV
  exportItems: async (id) => {
    const response = await axiosInstance.get(`${BASE}/${id}/export`, {
      responseType: 'blob',
    });
    return response.data;
  },

  // Import price list items from CSV file
  importItems: async (id, file) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await axiosInstance.post(`${BASE}/${id}/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  // Remove a product from price list
  removeItem: async (priceListId, productId) => {
    const response = await axiosInstance.delete(`${BASE}/${priceListId}/items/${productId}`);
    return response.data;
  },
};

export default priceListApi;
