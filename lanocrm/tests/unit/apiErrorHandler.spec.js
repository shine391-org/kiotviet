import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock antd message to avoid DOM usage
const errorMock = vi.fn();
const successMock = vi.fn();
vi.mock('antd', () => ({
  message: {
    error: (...args) => errorMock(...args),
    success: (...args) => successMock(...args),
  },
}));

import { handleApiError, extractApiMessage } from '../../src/utils/apiErrorHandler';

describe('apiErrorHandler', () => {
  beforeEach(() => {
    errorMock.mockClear();
    successMock.mockClear();
  });

  it('extracts message from response.data.message', () => {
    const error = { response: { data: { message: 'Mã sản phẩm đã tồn tại' } } };
    const message = handleApiError(error, { showMessage: false });
    expect(message).toBe('Mã sản phẩm đã tồn tại');
  });

  it('falls back to default message when none found', () => {
    const message = handleApiError({}, { showMessage: false });
    expect(message).toBe('Có lỗi xảy ra, vui lòng thử lại');
  });

  it('prefers data.error over generic error.message', () => {
    const error = {
      message: 'Client error',
      response: { data: { error: 'SKU đã tồn tại trong danh sách sản phẩm' } },
    };
    const message = handleApiError(error, { showMessage: false });
    expect(message).toBe('SKU đã tồn tại trong danh sách sản phẩm');
  });

  it('extractApiMessage returns provided default when error is null', () => {
    const msg = extractApiMessage(null, 'Fallback');
    expect(msg).toBe('Fallback');
  });
});
