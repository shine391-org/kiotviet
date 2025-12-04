import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import authApi from './authApi';

// Mock axios instance
vi.mock('./axios', () => ({
    default: {
        post: vi.fn(),
        get: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('authApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    describe('login', () => {
        it('should save token and user on successful login', async () => {
            const mockResponse = {
                data: {
                    token: 'mock-token-123',
                    user: {
                        id: '1',
                        username: 'testuser',
                        email: 'test@example.com',
                        permissions: ['products.view', 'products.create']
                    }
                }
            };
            axiosInstance.post.mockResolvedValue(mockResponse);

            const credentials = { username: 'testuser', password: 'password123' };
            const result = await authApi.login(credentials);

            expect(axiosInstance.post).toHaveBeenCalledWith('/auth/login', credentials);
            expect(result.success).toBe(true);
            expect(result.token).toBe('mock-token-123');
            expect(authApi.getToken()).toBe('mock-token-123');
            expect(authApi.getUser()).toMatchObject({
                id: '1',
                username: 'testuser'
            });
        });

        it('should throw error on invalid credentials', async () => {
            const mockError = new Error('Invalid credentials');
            mockError.response = { data: { message: 'Invalid credentials' } };
            axiosInstance.post.mockRejectedValue(mockError);

            const credentials = { username: 'wrong', password: 'wrong' };

            await expect(authApi.login(credentials)).rejects.toThrow();
        });
    });

    describe('logout', () => {
        it('should clear auth data', async () => {
            localStorage.setItem('lano_token', 'test-token');
            localStorage.setItem('lano_user', JSON.stringify({ id: '1', username: 'test' }));

            await authApi.logout();

            expect(authApi.getToken()).toBeNull();
            expect(authApi.getUser()).toBeNull();
        });
    });

    describe('token management', () => {
        it('should save and retrieve token', () => {
            authApi.saveToken('test-token');
            expect(authApi.getToken()).toBe('test-token');
        });

        it('should save and retrieve user', () => {
            const user = { id: '1', username: 'testuser', permissions: [] };
            authApi.saveUser(user);
            expect(authApi.getUser()).toMatchObject(user);
        });
    });

    describe('isAuthenticated', () => {
        it('should return true when token and user exist', () => {
            authApi.saveToken('test-token');
            authApi.saveUser({ id: '1', username: 'test', permissions: [] });
            expect(authApi.isAuthenticated()).toBe(true);
        });

        it('should return false when token or user missing', () => {
            expect(authApi.isAuthenticated()).toBe(false);
            authApi.saveToken('test-token');
            expect(authApi.isAuthenticated()).toBe(false);
        });
    });
});
