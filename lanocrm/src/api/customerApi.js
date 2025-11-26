/**
 * Customer API Service
 * @file src/api/customerApi.js
 * @description API calls for Customers module
 */

import axiosInstance from './axios';

const BASE = '/customers';

const customerApi = {
  /**
   * Fetch customers with filters + pagination.
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  getCustomers: async (params = {}) => {
    const response = await axiosInstance.get(BASE, {
      params: {
        page: params.page || 1,
        limit: params.limit || 15,
        search: params.search || undefined,
        customer_type: params.customer_type || undefined,
        gender: params.gender || undefined,
      },
    });
    return response.data;
  },

  /**
   * Get customer detail by id.
   * @param {number} id
   * @returns {Promise<Object>}
   */
  getCustomer: async (id) => {
    const response = await axiosInstance.get(`${BASE}/${id}`);
    return response.data;
  },

  /**
   * Create a new customer.
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  createCustomer: async (data) => {
    const response = await axiosInstance.post(BASE, data);
    return response.data;
  },

  /**
   * Update an existing customer.
   * @param {number} id
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  updateCustomer: async (id, data) => {
    const response = await axiosInstance.put(`${BASE}/${id}`, data);
    return response.data;
  },
};

export default customerApi;
