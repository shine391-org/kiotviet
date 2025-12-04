import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { message as antdMessage } from 'antd';
import {
    extractApiMessage,
    handleApiError,
    handleApiSuccess,
    validateRequired,
} from './apiErrorHandler';

// Mock antd message
vi.mock('antd', () => ({
    message: {
        error: vi.fn(),
        success: vi.fn(),
    },
}));

describe('apiErrorHandler', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        // Mock console.error to avoid noise in test output
        vi.spyOn(console, 'error').mockImplementation(() => { });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('extractApiMessage', () => {
        it('should return default message when error is null/undefined', () => {
            expect(extractApiMessage(null)).toBe('Có lỗi xảy ra, vui lòng thử lại');
            expect(extractApiMessage(undefined)).toBe('Có lỗi xảy ra, vui lòng thử lại');
        });

        it('should return custom default message', () => {
            const customDefault = 'Custom error message';
            expect(extractApiMessage(null, customDefault)).toBe(customDefault);
        });

        it('should extract message from response.data.message (Priority 1)', () => {
            const error = {
                response: {
                    data: {
                        message: 'Backend validation error',
                    },
                },
            };
            expect(extractApiMessage(error)).toBe('Backend validation error');
        });

        it('should extract message from response.data.messages.error (Priority 1)', () => {
            const error = {
                response: {
                    data: {
                        messages: {
                            error: 'Messages error field',
                        },
                    },
                },
            };
            expect(extractApiMessage(error)).toBe('Messages error field');
        });

        it('should extract message from response.data.error (Priority 2)', () => {
            const error = {
                response: {
                    data: {
                        error: 'Backend error field',
                    },
                },
            };
            expect(extractApiMessage(error)).toBe('Backend error field');
        });

        it('should extract message from error.message (Priority 3)', () => {
            const error = {
                message: 'Network Error',
            };
            expect(extractApiMessage(error)).toBe('Network Error');
        });

        it('should prioritize response.data.message over error.message', () => {
            const error = {
                response: {
                    data: {
                        message: 'Backend message',
                    },
                },
                message: 'Axios message',
            };
            expect(extractApiMessage(error)).toBe('Backend message');
        });

        it('should return default when no message found in error object', () => {
            const error = {
                response: {
                    data: {},
                },
            };
            expect(extractApiMessage(error)).toBe('Có lỗi xảy ra, vui lòng thử lại');
        });
    });

    describe('handleApiError', () => {
        it('should show error message by default', () => {
            const error = {
                response: {
                    data: {
                        message: 'Test error',
                    },
                },
            };

            const result = handleApiError(error);

            expect(result).toBe('Test error');
            expect(antdMessage.error).toHaveBeenCalledWith('Test error', 4);
        });

        it('should not show message when showMessage is false', () => {
            const error = {
                response: {
                    data: {
                        message: 'Test error',
                    },
                },
            };

            const result = handleApiError(error, { showMessage: false });

            expect(result).toBe('Test error');
            expect(antdMessage.error).not.toHaveBeenCalled();
        });

        it('should use custom duration', () => {
            const error = {
                response: {
                    data: {
                        message: 'Test error',
                    },
                },
            };

            handleApiError(error, { duration: 10 });

            expect(antdMessage.error).toHaveBeenCalledWith('Test error', 10);
        });

        it('should use custom default message', () => {
            const error = {};

            const result = handleApiError(error, {
                defaultMessage: 'Custom default',
            });

            expect(result).toBe('Custom default');
            expect(antdMessage.error).toHaveBeenCalledWith('Custom default', 4);
        });

        it('should use custom messageApi when provided', () => {
            const customMessageApi = {
                error: vi.fn(),
            };

            const error = {
                response: {
                    data: {
                        message: 'Test error',
                    },
                },
            };

            handleApiError(error, { messageApi: customMessageApi });

            expect(customMessageApi.error).toHaveBeenCalledWith('Test error', 4);
            expect(antdMessage.error).not.toHaveBeenCalled();
        });

        it('should log error in development mode', () => {
            const error = {
                response: {
                    status: 400,
                    data: {
                        message: 'Test error',
                    },
                },
                config: {
                    url: '/api/test',
                },
            };

            handleApiError(error);

            expect(console.error).toHaveBeenCalledWith(
                '[API Error]',
                expect.objectContaining({
                    message: 'Test error',
                    status: 400,
                    url: '/api/test',
                })
            );
        });
    });

    describe('handleApiSuccess', () => {
        it('should show success message by default', () => {
            const result = handleApiSuccess('Success message');

            expect(result).toBe('Success message');
            expect(antdMessage.success).toHaveBeenCalledWith('Success message', 3);
        });

        it('should not show message when showMessage is false', () => {
            const result = handleApiSuccess('Success message', { showMessage: false });

            expect(result).toBe('Success message');
            expect(antdMessage.success).not.toHaveBeenCalled();
        });

        it('should use custom duration', () => {
            handleApiSuccess('Success message', { duration: 5 });

            expect(antdMessage.success).toHaveBeenCalledWith('Success message', 5);
        });

        it('should use custom messageApi when provided', () => {
            const customMessageApi = {
                success: vi.fn(),
            };

            handleApiSuccess('Success message', { messageApi: customMessageApi });

            expect(customMessageApi.success).toHaveBeenCalledWith('Success message', 3);
            expect(antdMessage.success).not.toHaveBeenCalled();
        });
    });

    describe('validateRequired', () => {
        it('should return true when all required fields are present', () => {
            const formData = {
                name: 'John',
                email: 'john@example.com',
            };

            const result = validateRequired(formData, ['name', 'email']);

            expect(result).toBe(true);
            expect(antdMessage.error).not.toHaveBeenCalled();
        });

        it('should return false and show error when field is missing', () => {
            const formData = {
                name: 'John',
            };

            const result = validateRequired(formData, ['name', 'email']);

            expect(result).toBe(false);
            expect(antdMessage.error).toHaveBeenCalledWith('Vui lòng nhập email');
        });

        it('should return false when field is empty string', () => {
            const formData = {
                name: '   ',
            };

            const result = validateRequired(formData, ['name']);

            expect(result).toBe(false);
            expect(antdMessage.error).toHaveBeenCalledWith('Vui lòng nhập name');
        });

        it('should return false when field is null', () => {
            const formData = {
                name: null,
            };

            const result = validateRequired(formData, ['name']);

            expect(result).toBe(false);
        });

        it('should return false when field is undefined', () => {
            const formData = {};

            const result = validateRequired(formData, ['name']);

            expect(result).toBe(false);
        });

        it('should use custom field labels', () => {
            const formData = {};

            validateRequired(formData, ['email'], {
                fieldLabels: { email: 'Email address' },
            });

            expect(antdMessage.error).toHaveBeenCalledWith('Vui lòng nhập Email address');
        });

        it('should not show message when showMessage is false', () => {
            const formData = {};

            const result = validateRequired(formData, ['name'], { showMessage: false });

            expect(result).toBe(false);
            expect(antdMessage.error).not.toHaveBeenCalled();
        });

        it('should use custom messageApi when provided', () => {
            const customMessageApi = {
                error: vi.fn(),
            };

            const formData = {};

            validateRequired(formData, ['name'], { messageApi: customMessageApi });

            expect(customMessageApi.error).toHaveBeenCalledWith('Vui lòng nhập name');
            expect(antdMessage.error).not.toHaveBeenCalled();
        });

        it('should return true when no required fields specified', () => {
            const formData = {};

            const result = validateRequired(formData, []);

            expect(result).toBe(true);
        });

        it('should handle numeric values correctly', () => {
            const formData = {
                age: 0,
                count: 123,
            };

            const result = validateRequired(formData, ['age', 'count']);

            expect(result).toBe(true);
        });
    });
});
