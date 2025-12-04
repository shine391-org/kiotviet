import { describe, it, expect, vi, beforeEach } from 'vitest';
import cashApi from './cashApi';

vi.mock('./axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  }
}));

import axiosInstance from './axios';

describe('cashApi', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getTransactions sends params', async () => {
    const mockData = { data: [], pagination: {} };
    axiosInstance.get.mockResolvedValue({ data: mockData });

    const result = await cashApi.getTransactions({ type: 'RECEIPT', page: 2 });

    expect(axiosInstance.get).toHaveBeenCalledWith('/cash/transactions', { params: { type: 'RECEIPT', page: 2 } });
    expect(result).toEqual(mockData);
  });

  it('getTransaction hits detail endpoint', async () => {
    const mock = { data: { id: 1 } };
    axiosInstance.get.mockResolvedValue({ data: mock });

    const result = await cashApi.getTransaction(1);
    expect(axiosInstance.get).toHaveBeenCalledWith('/cash/transactions/1');
    expect(result).toEqual(mock);
  });

  it('createReceipt posts data', async () => {
    const payload = { amount: 1000 };
    const mock = { data: { id: 1 } };
    axiosInstance.post.mockResolvedValue({ data: mock });

    const result = await cashApi.createReceipt(payload);
    expect(axiosInstance.post).toHaveBeenCalledWith('/cash/receipt', payload);
    expect(result).toEqual(mock);
  });

  it('createPayment posts data', async () => {
    const payload = { amount: 500 };
    const mock = { data: { id: 2 } };
    axiosInstance.post.mockResolvedValue({ data: mock });

    const result = await cashApi.createPayment(payload);
    expect(axiosInstance.post).toHaveBeenCalledWith('/cash/payment', payload);
    expect(result).toEqual(mock);
  });

  it('deleteTransaction calls delete', async () => {
    const mock = { success: true };
    axiosInstance.delete.mockResolvedValue({ data: mock });

    const result = await cashApi.deleteTransaction(3);
    expect(axiosInstance.delete).toHaveBeenCalledWith('/cash/transactions/3');
    expect(result).toEqual(mock);
  });

  it('getBalance handles branch and all', async () => {
    axiosInstance.get.mockResolvedValueOnce({ data: { data: { balance: 10 } } });
    const overall = await cashApi.getBalance();
    expect(axiosInstance.get).toHaveBeenCalledWith('/cash/balance');
    expect(overall).toEqual({ data: { balance: 10 } });

    axiosInstance.get.mockResolvedValueOnce({ data: { data: { balance: 5 } } });
    const branch = await cashApi.getBalance(2);
    expect(axiosInstance.get).toHaveBeenCalledWith('/cash/balance/branch/2');
    expect(branch).toEqual({ data: { balance: 5 } });
  });

  it('getDailyReport passes params', async () => {
    const mock = { data: { receipt_total: 100 } };
    axiosInstance.get.mockResolvedValue({ data: mock });

    const result = await cashApi.getDailyReport('2025-11-27', 1);
    expect(axiosInstance.get).toHaveBeenCalledWith('/cash/report/daily', {
      params: { date: '2025-11-27', branch_id: 1 }
    });
    expect(result).toEqual(mock);
  });
});
