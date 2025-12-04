import { describe, it, expect, vi } from 'vitest';

vi.mock('@/api/axios', () => {
  return {
    default: {
      post: vi.fn((url, payload) => Promise.resolve({ data: { success: true, url, payload } })),
    },
  };
});

import axiosInstance from '@/api/axios';
import { createProduct } from '@/api/productApi';

describe('productApi createProduct normalization', () => {
  it('normalizes category_id and defaults numeric fields', async () => {
    const payload = {
      code: 'P1',
      name: 'P1',
      category_id: '5',
      product_type: 'goods',
      unit: 'cai',
    };

    const res = await createProduct(payload);

    expect(res.success).toBe(true);
    expect(axiosInstance.post).toHaveBeenCalledTimes(1);
    const sent = axiosInstance.post.mock.calls[0][1];
    expect(sent.category_id).toEqual([5]);
    expect(sent.selling_price).toBe(0);
    expect(sent.purchase_price).toBe(0);
    expect(sent.wholesale_price).toBe(0);
    expect(sent.stock_quantity).toBe(0);
  });
});
