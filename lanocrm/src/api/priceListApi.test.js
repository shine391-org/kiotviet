import { describe, it, expect, vi, beforeEach } from 'vitest';
import priceListApi from './priceListApi';

vi.mock('./axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  }
}));

import axiosInstance from './axios';

describe('priceListApi', () => {
  beforeEach(() => vi.clearAllMocks());

  it('fetches price lists', async () => {
    const mock = { data: [] };
    axiosInstance.get.mockResolvedValue({ data: mock });
    const result = await priceListApi.getPriceLists({ search: 'vip' });
    expect(axiosInstance.get).toHaveBeenCalledWith('/price-lists', { params: { search: 'vip' } });
    expect(result).toEqual(mock);
  });

  it('creates price list', async () => {
    const payload = { name: 'VIP' };
    const mock = { success: true };
    axiosInstance.post.mockResolvedValue({ data: mock });
    const result = await priceListApi.createPriceList(payload);
    expect(axiosInstance.post).toHaveBeenCalledWith('/price-lists', payload);
    expect(result).toEqual(mock);
  });

  it('updates price list', async () => {
    const mock = { success: true };
    axiosInstance.put.mockResolvedValue({ data: mock });
    const result = await priceListApi.updatePriceList(1, { name: 'New' });
    expect(axiosInstance.put).toHaveBeenCalledWith('/price-lists/1', { name: 'New' });
    expect(result).toEqual(mock);
  });

  it('deletes price list', async () => {
    const mock = { success: true };
    axiosInstance.delete.mockResolvedValue({ data: mock });
    const result = await priceListApi.deletePriceList(2);
    expect(axiosInstance.delete).toHaveBeenCalledWith('/price-lists/2');
    expect(result).toEqual(mock);
  });

  it('saves items', async () => {
    const mock = { success: true };
    axiosInstance.post.mockResolvedValue({ data: mock });
    const result = await priceListApi.saveItems(1, [{ product_id: 1 }]);
    expect(axiosInstance.post).toHaveBeenCalledWith('/price-lists/1/items', { items: [{ product_id: 1 }] });
    expect(result).toEqual(mock);
  });

  it('previews order pricing', async () => {
    const mock = { success: true };
    axiosInstance.post.mockResolvedValue({ data: mock });
    const payload = { items: [] };
    const result = await priceListApi.previewOrder(payload);
    expect(axiosInstance.post).toHaveBeenCalledWith('/orders/calculate-preview', payload);
    expect(result).toEqual(mock);
  });
});
