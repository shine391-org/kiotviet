import { api } from './api';

export const loyaltyApi = {
  getWallet: (customerId) => api.get(`/loyalty/wallet/${customerId}`),
  calculateEarn: (customerId, orderTotal) =>
    api.post('/loyalty/calculate-earn', { customer_id: customerId, order_total: orderTotal }),
  redeemPreview: (customerId, points) =>
    api.post('/loyalty/redeem-preview', { customer_id: customerId, points }),
  earn: (customerId, orderTotal, orderId = null) =>
    api.post('/loyalty/earn', { customer_id: customerId, order_total: orderTotal, order_id: orderId }),
  redeem: (customerId, points, orderId = null) =>
    api.post('/loyalty/redeem', { customer_id: customerId, points, order_id: orderId }),
  getTransactions: (customerId) => api.get(`/loyalty/transactions/${customerId}`),
};

export default loyaltyApi;
