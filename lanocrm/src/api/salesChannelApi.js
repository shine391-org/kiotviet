import { api } from './api';

export const salesChannelApi = {
  getAll: (params = {}) => api.get('/sales-channels', { params }),
  getById: (id) => api.get(`/sales-channels/${id}`),
  create: (data) => api.post('/sales-channels', data),
  update: (id, data) => api.put(`/sales-channels/${id}`, data),
  delete: (id) => api.delete(`/sales-channels/${id}`),
};

export default salesChannelApi;
