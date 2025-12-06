/**
 * Delivery Partner API Service
 * @file src/api/deliveryPartnerApi.js
 * @description API calls for self-delivery partners (đối tác giao hàng tự giao)
 */

import axiosInstance from './axios';

const BASE = '/delivery-partners';

const deliveryPartnerApi = {
  /**
   * List delivery partners with filters and pagination
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  list: async (params = {}) => {
    const response = await axiosInstance.get(BASE, {
      params: {
        page: params.page || 1,
        limit: params.limit || 20,
        search: params.search || undefined,
        status: params.status || undefined,
        sort_by: params.sort_by || 'created_at',
        sort_order: params.sort_order || 'desc',
      },
    });
    return response.data;
  },

  /**
   * Get delivery partner detail
   * @param {number} id - Partner ID
   * @returns {Promise<Object>}
   */
  get: async (id) => {
    const response = await axiosInstance.get(`${BASE}/${id}`);
    return response.data;
  },

  /**
   * Create new delivery partner
   * @param {Object} data - Partner data
   * @returns {Promise<Object>}
   */
  create: async (data) => {
    const response = await axiosInstance.post(BASE, data);
    return response.data;
  },

  /**
   * Update delivery partner
   * @param {number} id - Partner ID
   * @param {Object} data - Updated data
   * @returns {Promise<Object>}
   */
  update: async (id, data) => {
    const response = await axiosInstance.put(`${BASE}/${id}`, data);
    return response.data;
  },

  /**
   * Delete delivery partner
   * @param {number} id - Partner ID
   * @returns {Promise<Object>}
   */
  delete: async (id) => {
    const response = await axiosInstance.delete(`${BASE}/${id}`);
    return response.data;
  },
};

export default deliveryPartnerApi;
