import { api } from './api';

export const posSalesApi = {
  quickReturn: (data) => api.post('/pos/quick-return', data),
  getDailyReport: (params = {}) => api.get('/pos/daily-report', { params }),
  getSellers: (params = {}) => api.get('/pos/sellers', { params }),
};

export default posSalesApi;
