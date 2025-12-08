/**
 * POS API Service
 * @file src/api/posApi.js
 * @description API calls for POS Sales page
 */

import axiosInstance from './axios';

const posApi = {
  /**
   * Search products for POS
   * @param {Object} params - Search parameters
   * @returns {Promise<Object>}
   */
  searchProducts: async (params = {}) => {
    const response = await axiosInstance.get('/products', {
      params: {
        search: params.search || undefined,
        limit: params.limit || 20,
        page: params.page || 1,
        is_active: 1,
        status: 'active',
      },
    });
    return response.data;
  },

  /**
   * Search customers for POS
   * @param {string|Object} searchOrParams - Search keyword or parameters object
   * @returns {Promise<Object>}
   */
  searchCustomers: async (searchOrParams) => {
    const params = typeof searchOrParams === 'string'
      ? { search: searchOrParams }
      : searchOrParams;
    const response = await axiosInstance.get('/customers', {
      params: {
        search: params.search || undefined,
        limit: params.limit || 10,
        page: params.page || 1,
        status: 'ACTIVE',
      },
    });
    return response.data;
  },

  /**
   * Create POS sale/order
   * @param {Object} data - Order data
   * @returns {Promise<Object>}
   */
  createSale: async (data) => {
    const payload = {
      order_type: data.order_type || 'pos',
      customer_id: data.customer_id || null,
      branch_id: data.branch_id || 1,
      user_id: data.user_id || null,
      order_date: data.order_date || new Date().toISOString().split('T')[0],
      items: data.items.map(item => ({
        product_id: item.product_id || item.id,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
      })),
      payments: data.payments || [{
        payment_method: data.payment_method || 'CASH',
        amount: data.total || 0,
      }],
      shipping_fee: data.shipping_fee || 0,
      shipping: {
        name: data.shipping_name || '',
        phone: data.shipping_phone || '',
        address: data.shipping_address || '',
        ward: data.shipping_ward || '',
        district: data.shipping_district || '',
        city: data.shipping_city || '',
      },
      notes: data.notes || '',
    };

    console.log('🔵 posApi.createSale final payload:', payload);
    const response = await axiosInstance.post('/orders', payload);
    return response.data;
  },

  /**
   * Preview order totals (calculate before submit)
   * @param {Object} data - Order data
   * @returns {Promise<Object>}
   */
  previewSale: async (data) => {
    const payload = {
      customer_id: data.customer_id || null,
      order_date: data.order_date || new Date().toISOString().split('T')[0],
      items: data.items.map(item => ({
        product_id: item.product_id || item.id,
        variant_id: item.variant_id || null,
        quantity: item.quantity,
      })),
    };

    const response = await axiosInstance.post('/orders/calculate-preview', payload);
    return response.data;
  },

  /**
   * Get payment methods
   * @returns {Promise<Object>}
   */
  getPaymentMethods: async () => {
    const response = await axiosInstance.get('/payment-methods');
    return response.data;
  },

  /**
   * Get branches
   * @returns {Promise<Object>}
   */
  getBranches: async () => {
    const response = await axiosInstance.get('/branches');
    return response.data;
  },

  /**
   * Get orders for return/process
   * @param {Object} params - Filter parameters
   * @returns {Promise<Object>}
   */
  getOrders: async (params = {}) => {
    const response = await axiosInstance.get('/orders', {
      params: {
        search: params.search || undefined,
        status: params.status || undefined,
        limit: params.limit || 20,
        page: params.page || 1,
      },
    });
    return response.data;
  },

  /**
   * Get completed orders for return (returnable invoices)
   * @param {Object} params - Filter parameters
   * @returns {Promise<Object>}
   */
  getInvoices: async (params = {}) => {
    const response = await axiosInstance.get('/orders', {
      params: {
        search: params.search || undefined,
        limit: params.limit || 20,
        page: params.page || 1,
        search_type: params.searchType || undefined,
        from_date: params.fromDate || undefined,
        to_date: params.toDate || undefined,
        // Only get completed/delivered orders that can be returned
        status: params.status || 'COMPLETED,DELIVERED',
        order_type: 'pos',
      },
    });
    return response.data;
  },

  /**
   * Create customer quickly from POS
   * @param {Object} data - Customer data
   * @returns {Promise<Object>}
   */
  createCustomer: async (data) => {
    const response = await axiosInstance.post('/customers', {
      name: data.customerName || data.name,
      phone: data.phone,
      email: data.email || undefined,
      address: data.address || undefined,
      gender: data.gender || undefined,
      birthday: data.birthday || undefined,
      customer_type: data.customerType || 'INDIVIDUAL',
      notes: data.note || data.notes || undefined,
    });
    return response.data;
  },

  /**
   * Create product quickly from POS
   * @param {Object} data - Product data
   * @returns {Promise<Object>}
   */
  createProduct: async (data) => {
    const response = await axiosInstance.post('/products', {
      code: data.productCode || `SP${Date.now()}`,
      name: data.productName || data.name,
      selling_price: data.salePrice || data.selling_price || 0,
      purchase_price: data.costPrice || data.purchase_price || 0,
      stock_quantity: data.stock || data.stock_quantity || 0,
      unit: data.unit || 'Cái',
      product_type: 'goods',
      category_id: data.category ? [data.category] : [],
    });
    return response.data;
  },

  /**
   * Get sellers/staff for POS
   * @returns {Promise<Object>}
   */
  getSellers: async () => {
    const response = await axiosInstance.get('/users', {
      params: {
        limit: 50,
        status: 'active',
      },
    });
    return response.data;
  },

  /**
   * Get daily report for POS end-of-day
   * @param {Object} params - Filter parameters {date, branch_id, user_id}
   * @returns {Promise<Object>}
   */
  getDailyReport: async (params = {}) => {
    const response = await axiosInstance.get('/pos/daily-report', {
      params: {
        date: params.date || undefined,
        branch_id: params.branch_id || undefined,
        user_id: params.user_id || undefined,
      },
    });
    return response.data;
  },
};

export default posApi;
