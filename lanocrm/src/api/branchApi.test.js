import { describe, it, expect, vi, beforeEach } from 'vitest';
import branchApi from './branchApi';

vi.mock('./axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('branchApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('getBranches', () => {
        it('should fetch branches with params', async () => {
            const mockData = { data: [{ id: '1', name: 'Branch 1' }], pagination: {} };
            axiosInstance.get.mockResolvedValue({ data: mockData });

            const result = await branchApi.getBranches({ page: 1 });

            expect(axiosInstance.get).toHaveBeenCalledWith('/branches', { params: { page: 1 } });
            expect(result).toEqual(mockData);
        });
    });

    describe('getBranchById', () => {
        it('should fetch branch by id', async () => {
            const mockBranch = { data: { id: '1', name: 'Branch 1' } };
            axiosInstance.get.mockResolvedValue({ data: mockBranch });

            const result = await branchApi.getBranchById('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/branches/1');
            expect(result).toEqual(mockBranch);
        });
    });

    describe('createBranch', () => {
        it('should create new branch', async () => {
            const branchData = { name: 'New Branch', address: '123 Street' };
            const mockResponse = { data: { id: '2', ...branchData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await branchApi.createBranch(branchData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/branches', branchData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('updateBranch', () => {
        it('should update branch', async () => {
            const branchData = { name: 'Updated Branch' };
            const mockResponse = { data: { id: '1', ...branchData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await branchApi.updateBranch('1', branchData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/branches/1', branchData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('deleteBranch', () => {
        it('should delete branch', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await branchApi.deleteBranch('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/branches/1');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('exportBranches', () => {
        it('should export branches as blob', async () => {
            const mockBlob = new Blob(['data'], { type: 'text/csv' });
            axiosInstance.get.mockResolvedValue({ data: mockBlob });

            const result = await branchApi.exportBranches({ format: 'csv' });

            expect(axiosInstance.get).toHaveBeenCalledWith('/branches/export', {
                params: { format: 'csv' },
                responseType: 'blob'
            });
            expect(result).toEqual(mockBlob);
        });
    });

    describe('setDefaultBranch', () => {
        it('should set default branch', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await branchApi.setDefaultBranch('1');

            expect(axiosInstance.post).toHaveBeenCalledWith('/branches/set_default/1');
            expect(result).toEqual(mockResponse);
        });

        it('should handle errors', async () => {
            const mockError = { response: { data: { message: 'Error' } } };
            axiosInstance.post.mockRejectedValue(mockError);

            await expect(branchApi.setDefaultBranch('1')).rejects.toThrow();
        });
    });
});
