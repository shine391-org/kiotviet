/**
 * Supplier API - connects to backend /api/suppliers endpoints
 * @agent-layer: frontend-api
 * @agent-pattern: api-client
 * @file src/api/supplierApi.js
 */

import axiosInstance from './axios';

const supplierApi = {
  /**
   * List suppliers with filters and pagination.
   * GET /api/suppliers
   */
  getSuppliers: async (params = {}) => {
    const response = await axiosInstance.get('/suppliers', { params });
    return {
      data: response.data.data || [],
      pagination: response.data.pagination || {
        page: 1,
        limit: 15,
        total: 0,
        total_pages: 1,
      },
      summary: response.data.summary || {
        total_debt: 0,
        total_purchase: 0,
      },
    };
  },

  /**
   * Get single supplier by ID.
   * GET /api/suppliers/:id
   */
  getSupplier: async (id) => {
    const response = await axiosInstance.get(`/suppliers/${id}`);
    return { data: response.data.data };
  },

  /**
   * Create new supplier.
   * POST /api/suppliers
   */
  createSupplier: async (body) => {
    const response = await axiosInstance.post('/suppliers', body);
    return { data: response.data.data };
  },

  /**
   * Update existing supplier.
   * PUT /api/suppliers/:id
   */
  updateSupplier: async (id, body) => {
    const response = await axiosInstance.put(`/suppliers/${id}`, body);
    return { data: response.data.data };
  },

  /**
   * Delete supplier.
   * DELETE /api/suppliers/:id
   */
  deleteSupplier: async (id) => {
    const response = await axiosInstance.delete(`/suppliers/${id}`);
    return { success: true, message: response.data.message };
  },

  // ============== DEBT OPERATIONS ==============

  /**
   * Adjust supplier debt.
   * POST /api/suppliers/:id/adjust
   */
  adjustDebt: async (id, body) => {
    const response = await axiosInstance.post(`/suppliers/${id}/adjust`, body);
    return response.data;
  },

  /**
   * Create payment for supplier debt.
   * POST /api/suppliers/:id/payment
   */
  createPayment: async (id, body) => {
    const response = await axiosInstance.post(`/suppliers/${id}/payment`, body);
    return response.data;
  },

  /**
   * Create discount for supplier.
   * POST /api/suppliers/:id/discount
   */
  createDiscount: async (id, body) => {
    const response = await axiosInstance.post(`/suppliers/${id}/discount`, body);
    return response.data;
  },

  /**
   * Get supplier debt history.
   * GET /api/suppliers/:id/debt-history
   */
  getDebtHistory: async (id, limit = 50) => {
    const response = await axiosInstance.get(`/suppliers/${id}/debt-history`, { params: { limit } });
    return response.data;
  },
};

export default supplierApi;
