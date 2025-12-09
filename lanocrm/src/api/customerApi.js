/**
 * Customer API Service
 * @file src/api/customerApi.js
 * @description API calls for Customers module
 */

import axiosInstance from './axios';

const BASE = '/customers';
const GROUPS_BASE = '/customer-groups';

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
        status: params.status || undefined,
        created_from: params.created_from || undefined,
        created_to: params.created_to || undefined,
        birthday_from: params.birthday_from || undefined,
        birthday_to: params.birthday_to || undefined,
        last_transaction_from: params.last_transaction_from || undefined,
        last_transaction_to: params.last_transaction_to || undefined,
        debt_from: params.debt_from || undefined,
        debt_to: params.debt_to || undefined,
        total_sales_from: params.total_sales_from || undefined,
        total_sales_to: params.total_sales_to || undefined,
        customer_group_id: params.customer_group_id || undefined,
        created_by: params.created_by || undefined,
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

  /**
   * Delete a customer (soft delete).
   * @param {number} id
   * @returns {Promise<Object>}
   */
  deleteCustomer: async (id) => {
    const response = await axiosInstance.delete(`${BASE}/${id}`);
    return response.data;
  },

  exportCustomers: async (params = {}) => {
    const response = await axiosInstance.get(`${BASE}/export`, {
      params,
      responseType: 'blob',
    });
    return response;
  },

  importCustomers: async (file) => {
    const formData = new FormData();
    formData.append('file', file);
    const response = await axiosInstance.post(`${BASE}/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  // Customer Groups
  getGroups: async (params = {}) => {
    const response = await axiosInstance.get(GROUPS_BASE, { params });
    return response.data;
  },

  getGroup: async (id) => {
    const response = await axiosInstance.get(`${GROUPS_BASE}/${id}`);
    return response.data;
  },

  createGroup: async (data) => {
    const response = await axiosInstance.post(GROUPS_BASE, data);
    return response.data;
  },

  updateGroup: async (id, data) => {
    const response = await axiosInstance.put(`${GROUPS_BASE}/${id}`, data);
    return response.data;
  },

  deleteGroup: async (id) => {
    const response = await axiosInstance.delete(`${GROUPS_BASE}/${id}`);
    return response.data;
  },

  // Customer Addresses (for "Địa chỉ nhận hàng" tab)
  getAddresses: async (customerId) => {
    const response = await axiosInstance.get(`${BASE}/${customerId}/addresses`);
    return response.data;
  },

  createAddress: async (customerId, data) => {
    const response = await axiosInstance.post(`${BASE}/${customerId}/addresses`, data);
    return response.data;
  },

  updateAddress: async (customerId, addressId, data) => {
    const response = await axiosInstance.put(`${BASE}/${customerId}/addresses/${addressId}`, data);
    return response.data;
  },

  deleteAddress: async (customerId, addressId) => {
    const response = await axiosInstance.delete(`${BASE}/${customerId}/addresses/${addressId}`);
    return response.data;
  },

  // Customer Debt Operations (for "Nợ cần thu từ khách" tab)
  getDebts: async (customerId, type = 'all') => {
    const response = await axiosInstance.get(`${BASE}/${customerId}/debts`, { params: { type } });
    return response.data;
  },

  recordPayment: async (customerId, data) => {
    const response = await axiosInstance.post(`${BASE}/${customerId}/debts/payment`, data);
    return response.data;
  },

  adjustDebt: async (customerId, data) => {
    const response = await axiosInstance.post(`${BASE}/${customerId}/debts/adjust`, data);
    return response.data;
  },

  applyDiscount: async (customerId, data) => {
    const response = await axiosInstance.post(`${BASE}/${customerId}/debts/discount`, data);
    return response.data;
  },
};

export default customerApi;
