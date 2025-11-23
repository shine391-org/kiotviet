import { describe, it, expect, vi, beforeEach } from 'vitest';
import userApi from './userApi';

vi.mock('./axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('userApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('getUsers', () => {
        it('should fetch users with params', async () => {
            const mockData = { data: [{ id: '1', username: 'user1' }], pagination: {} };
            axiosInstance.get.mockResolvedValue({ data: mockData });

            const result = await userApi.getUsers({ page: 1, limit: 10 });

            expect(axiosInstance.get).toHaveBeenCalledWith('/users', { params: { page: 1, limit: 10 } });
            expect(result).toEqual(mockData);
        });
    });

    describe('getUserById', () => {
        it('should fetch user by id', async () => {
            const mockUser = { data: { id: '1', username: 'user1' } };
            axiosInstance.get.mockResolvedValue({ data: mockUser });

            const result = await userApi.getUserById('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/users/1');
            expect(result).toEqual(mockUser);
        });
    });

    describe('createUser', () => {
        it('should create new user', async () => {
            const userData = { username: 'newuser', email: 'new@example.com' };
            const mockResponse = { data: { id: '2', ...userData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await userApi.createUser(userData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/users/create', userData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('updateUser', () => {
        it('should update user', async () => {
            const userData = { email: 'updated@example.com' };
            const mockResponse = { data: { id: '1', ...userData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await userApi.updateUser('1', userData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/users/update/1', userData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('deleteUser', () => {
        it('should delete user', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await userApi.deleteUser('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/users/delete/1');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('getRoles', () => {
        it('should fetch available roles', async () => {
            const mockRoles = { data: [{ id: '1', name: 'Admin' }] };
            axiosInstance.get.mockResolvedValue({ data: mockRoles });

            const result = await userApi.getRoles();

            expect(axiosInstance.get).toHaveBeenCalledWith('/users/roles');
            expect(result).toEqual(mockRoles);
        });
    });

    describe('getBranches', () => {
        it('should fetch available branches', async () => {
            const mockBranches = { data: [{ id: '1', name: 'Main Branch' }] };
            axiosInstance.get.mockResolvedValue({ data: mockBranches });

            const result = await userApi.getBranches();

            expect(axiosInstance.get).toHaveBeenCalledWith('/users/branches');
            expect(result).toEqual(mockBranches);
        });
    });

    describe('changePassword', () => {
        it('should change user password', async () => {
            const passwordData = { new_password: 'newpass123' };
            const mockResponse = { data: { success: true } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await userApi.changePassword('1', passwordData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/users/1/change-password', passwordData);
            expect(result).toEqual(mockResponse);
        });
    });
});
