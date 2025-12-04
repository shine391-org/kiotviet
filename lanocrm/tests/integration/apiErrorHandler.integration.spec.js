import { describe, it, expect, vi } from 'vitest';

// Mock antd message
const errorMock = vi.fn();
vi.mock('antd', () => ({
  message: { error: (...args) => errorMock(...args), success: () => {} },
}));

import { handleApiError } from '../../src/utils/apiErrorHandler';

describe('apiErrorHandler integration', () => {
  it('picks messages.error when present', () => {
    const error = { response: { data: { messages: { error: 'Mã sản phẩm đã tồn tại trong danh sách sản phẩm' } } } };
    const msg = handleApiError(error, { showMessage: false });
    expect(msg).toBe('Mã sản phẩm đã tồn tại trong danh sách sản phẩm');
  });
});
