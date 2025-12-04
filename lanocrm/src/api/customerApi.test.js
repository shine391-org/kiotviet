import { describe, it, expect, vi, beforeEach } from 'vitest';
import customerApi from './customerApi';

vi.mock('./axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
  }
}));

import axiosInstance from './axios';

describe('customerApi', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('fetches customers with default pagination', async () => {
    const payload = { data: [] };
    axiosInstance.get.mockResolvedValue({ data: payload });

    const result = await customerApi.getCustomers({});

    expect(axiosInstance.get).toHaveBeenCalledWith('/customers', {
      params: { page: 1, limit: 15, search: undefined, customer_type: undefined, gender: undefined },
    });
    expect(result).toEqual(payload);
  });

  it('fetches customers with filters', async () => {
    const payload = { data: [] };
    axiosInstance.get.mockResolvedValue({ data: payload });

    const params = { page: 2, limit: 50, search: 'Lan', customer_type: 'COMPANY', gender: 'FEMALE', status: 'ACTIVE' };
    await customerApi.getCustomers(params);

    expect(axiosInstance.get).toHaveBeenCalledWith('/customers', {
      params: params,
    });
  });

  it('gets customer detail', async () => {
    const payload = { data: { id: 1 } };
    axiosInstance.get.mockResolvedValue({ data: payload });

    const result = await customerApi.getCustomer(1);

    expect(axiosInstance.get).toHaveBeenCalledWith('/customers/1');
    expect(result).toEqual(payload);
  });

  it('creates customer', async () => {
    const payload = { success: true };
    axiosInstance.post.mockResolvedValue({ data: payload });

    const body = { name: 'Lan' };
    const result = await customerApi.createCustomer(body);

    expect(axiosInstance.post).toHaveBeenCalledWith('/customers', body);
    expect(result).toEqual(payload);
  });

  it('updates customer', async () => {
    const payload = { success: true };
    axiosInstance.put.mockResolvedValue({ data: payload });

    const body = { name: 'Lan Updated' };
    const result = await customerApi.updateCustomer(3, body);

    expect(axiosInstance.put).toHaveBeenCalledWith('/customers/3', body);
    expect(result).toEqual(payload);
  });

  it('exports customers', async () => {
    const blobResponse = { data: 'csv', headers: {} };
    axiosInstance.get.mockResolvedValue(blobResponse);
    const result = await customerApi.exportCustomers({ search: 'a' });
    expect(axiosInstance.get).toHaveBeenCalledWith('/customers/export', { params: { search: 'a' }, responseType: 'blob' });
    expect(result).toEqual(blobResponse);
  });

  it('imports customers', async () => {
    const payload = { success: true };
    axiosInstance.post.mockResolvedValue({ data: payload });
    const file = new File(['name,phone'], 'customers.csv', { type: 'text/csv' });
    const result = await customerApi.importCustomers(file);
    expect(axiosInstance.post).toHaveBeenCalledWith('/customers/import', expect.any(FormData), expect.any(Object));
    expect(result).toEqual(payload);
  });
});
