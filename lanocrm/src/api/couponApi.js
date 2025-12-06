import { api } from './api';

export const couponApi = {
  getAll: (params = {}) => api.get('/coupons', { params }),
  getById: (id) => api.get(`/coupons/${id}`),
  create: (data) => api.post('/coupons', data),
  update: (id, data) => api.put(`/coupons/${id}`, data),
  delete: (id) => api.delete(`/coupons/${id}`),
  apply: (code, orderTotal, customerId = null) =>
    api.post('/coupons/apply', { code, order_total: orderTotal, customer_id: customerId }),
};

export default couponApi;
