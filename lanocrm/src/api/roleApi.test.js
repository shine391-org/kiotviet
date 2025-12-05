import { describe, it, expect, vi, beforeEach } from 'vitest';
import roleApi from './roleApi';

vi.mock('./axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('roleApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('getRoles', () => {
        it('should fetch roles list', async () => {
            const mockData = { data: [{ id: '1', name: 'Admin' }] };
            axiosInstance.get.mockResolvedValue({ data: mockData });

            const result = await roleApi.getRoles({ page: 1 });

            expect(axiosInstance.get).toHaveBeenCalledWith('/roles', { params: { page: 1 } });
            expect(result).toEqual(mockData);
        });
    });

    describe('getRoleById', () => {
        it('should fetch role by id', async () => {
            const mockRole = { id: '1', name: 'Admin' };
            axiosInstance.get.mockResolvedValue({ data: { data: mockRole } });

            const result = await roleApi.getRoleById('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/roles/1');
            expect(result).toEqual(mockRole);
        });
    });

    describe('createRole', () => {
        it('should create new role', async () => {
            const roleData = { name: 'Manager', description: 'Manager role' };
            const mockResponse = { data: { id: '2', ...roleData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await roleApi.createRole(roleData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/roles/create', roleData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('updateRole', () => {
        it('should update role', async () => {
            const roleData = { name: 'Updated Manager' };
            const mockResponse = { data: { id: '1', ...roleData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await roleApi.updateRole('1', roleData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/roles/update/1', roleData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('deleteRole', () => {
        it('should delete role', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await roleApi.deleteRole('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/roles/delete/1');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('getRolePermissions', () => {
        it('should fetch role permissions', async () => {
            const mockPermissions = [{ id: '1', name: 'products.view' }];
            axiosInstance.get.mockResolvedValue({ data: { data: mockPermissions } });

            const result = await roleApi.getRolePermissions('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/roles/1/permissions');
            expect(result).toEqual(mockPermissions);
        });
    });

    describe('assignRolePermissions', () => {
        it('should assign permissions to role', async () => {
            const permissionIds = ['1', '2', '3'];
            const mockResponse = { data: { success: true } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await roleApi.assignRolePermissions('1', permissionIds);

            expect(axiosInstance.post).toHaveBeenCalledWith('/roles/1/assign-permissions', {
                permission_ids: permissionIds
            });
            expect(result).toEqual(mockResponse);
        });
    });

    describe('getAllPermissions', () => {
        it('should fetch and group all permissions by module', async () => {
            const mockPermissions = [
                { id: '1', name: 'products.view', module: 'products' },
                { id: '2', name: 'products.create', module: 'products' },
                { id: '3', name: 'users.view', module: 'users' }
            ];
            axiosInstance.get.mockResolvedValue({ data: { data: mockPermissions } });

            const result = await roleApi.getAllPermissions();

            expect(axiosInstance.get).toHaveBeenCalledWith('/permissions');
            expect(result).toHaveProperty('products');
            expect(result).toHaveProperty('users');
            expect(result.products).toHaveLength(2);
            expect(result.users).toHaveLength(1);
        });
    });
});
