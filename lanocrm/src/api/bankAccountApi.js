import { api } from './api';

export const bankAccountApi = {
  getAll: (params = {}) => api.get('/bank-accounts', { params }),
  getById: (id) => api.get(`/bank-accounts/${id}`),
  create: (data) => api.post('/bank-accounts', data),
  update: (id, data) => api.put(`/bank-accounts/${id}`, data),
  delete: (id) => api.delete(`/bank-accounts/${id}`),
  getQR: (id, amount, description = '') =>
    api.get(`/bank-accounts/${id}/qr`, { params: { amount, description } }),
};

export default bankAccountApi;
