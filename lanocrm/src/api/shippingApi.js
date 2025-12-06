import { api } from './api';

export const shippingApi = {
  calculateFee: (data) => api.post('/shipping/calculate', data),
  
  // Zones
  getZones: (params = {}) => api.get('/shipping/zones', { params }),
  createZone: (data) => api.post('/shipping/zones', data),
  updateZone: (id, data) => api.put(`/shipping/zones/${id}`, data),
  deleteZone: (id) => api.delete(`/shipping/zones/${id}`),
  
  // Rates
  getRates: (zoneId) => api.get(`/shipping/zones/${zoneId}/rates`),
  createRate: (data) => api.post('/shipping/rates', data),
  updateRate: (id, data) => api.put(`/shipping/rates/${id}`, data),
  deleteRate: (id) => api.delete(`/shipping/rates/${id}`),
};

export default shippingApi;
