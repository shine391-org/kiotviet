import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Local mocks for axios instance
const getMock = vi.fn();
const postMock = vi.fn();

vi.mock('@/api/axios', () => ({
  default: {
    get: (...args) => getMock(...args),
    post: (...args) => postMock(...args),
  },
}));

import {
  getMediaLibrary,
  searchMediaBySku,
  attachMultipleImages,
} from '@/api/productApi';

describe('Media library FE-BE contract', () => {
  beforeEach(() => {
    getMock.mockReset();
    postMock.mockReset();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('maps backend pagination + data when listing media library', async () => {
    getMock.mockResolvedValue({
      data: {
        success: true,
        data: [
          { id: 1, image_url: '/a.jpg', is_attached: true },
          { id: 2, image_url: '/b.jpg', is_attached: false },
        ],
        pagination: { total: 2, limit: 20, offset: 0, pages: 1 },
        message: 'ok',
      },
    });

    const res = await getMediaLibrary(20, 0, { entity_id: 10 });

    expect(getMock).toHaveBeenCalledTimes(1);
    const callArgs = getMock.mock.calls[0][1];
    expect(callArgs.params.limit).toBe(20);
    expect(callArgs.params.offset).toBe(0);
    expect(callArgs.params.entity_id).toBe(10);

    expect(res.success).toBe(true);
    expect(res.data).toHaveLength(2);
    expect(res.total).toBe(2);
    expect(res.limit).toBe(20);
    expect(res.offset).toBe(0);
    expect(res.pages).toBe(1);
  });

  it('returns backend counts/message for attachMultipleImages', async () => {
    postMock.mockResolvedValue({
      data: {
        success: true,
        message: 'Đã thêm 1 ảnh, 1 ảnh bị bỏ qua (đã tồn tại)',
        attached_count: 1,
        skipped_count: 1,
        missing_count: 0,
        attached_ids: [2],
        skipped_ids: [1],
        missing_ids: [],
      },
    });

    const res = await attachMultipleImages(99, [1, 2]);

    expect(postMock).toHaveBeenCalledTimes(1);
    expect(postMock.mock.calls[0][0]).toContain('/products/99/images/attach-multiple');
    expect(res.attached_count).toBe(1);
    expect(res.message).toContain('bỏ qua');
    expect(res.success).toBe(true);
  });

  it('passes searchMediaBySku params to backend and maps totals', async () => {
    getMock.mockResolvedValue({
      data: {
        success: true,
        data: [{ id: 5, image_url: '/sku.jpg', is_attached: true }],
        total: 1,
        message: 'ok',
      },
    });

    const res = await searchMediaBySku('SKU-ABC', 5);

    expect(getMock).toHaveBeenCalledTimes(1);
    const params = getMock.mock.calls[0][1].params;
    expect(params.sku).toBe('SKU-ABC');
    expect(params.limit).toBe(5);

    expect(res.total).toBe(1);
    expect(res.data[0].id).toBe(5);
    expect(res.success).toBe(true);
  });
});
