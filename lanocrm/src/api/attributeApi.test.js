import { describe, it, expect, vi, beforeEach } from 'vitest';
import attributeApi from './attributeApi';

vi.mock('./axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('attributeApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('getAttributes', () => {
        it('should fetch attributes list', async () => {
            const mockData = { success: true, data: [{ id: '1', name: 'Color' }] };
            axiosInstance.get.mockResolvedValue({ data: mockData });

            const result = await attributeApi.getAttributes({ page: 1 });

            expect(axiosInstance.get).toHaveBeenCalledWith('/attributes', { params: { page: 1 } });
            expect(result.success).toBe(true);
            expect(result.data).toEqual([{ id: '1', name: 'Color' }]);
        });
    });

    describe('getAttributeDetail', () => {
        it('should fetch attribute by id', async () => {
            const mockAttribute = { success: true, data: { id: '1', name: 'Color' } };
            axiosInstance.get.mockResolvedValue({ data: mockAttribute });

            const result = await attributeApi.getAttributeDetail('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/attributes/1');
            expect(result.success).toBe(true);
            expect(result.data).toEqual({ id: '1', name: 'Color' });
        });
    });

    describe('createAttribute', () => {
        it('should create new attribute', async () => {
            const attributeData = { name: 'Size', type: 'select' };
            const mockResponse = { success: true, data: { id: '2', ...attributeData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.createAttribute(attributeData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/attributes', attributeData);
            expect(result.success).toBe(true);
            expect(result.data.name).toBe('Size');
        });
    });

    describe('updateAttribute', () => {
        it('should update attribute', async () => {
            const attributeData = { name: 'Updated Color' };
            const mockResponse = { success: true, data: { id: '1', ...attributeData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.updateAttribute('1', attributeData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/attributes/1', attributeData);
            expect(result.success).toBe(true);
        });
    });

    describe('deleteAttribute', () => {
        it('should delete attribute', async () => {
            const mockResponse = { success: true, message: 'Deleted' };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.deleteAttribute('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/attributes/1');
            expect(result.success).toBe(true);
        });
    });

    describe('getAttributeOptions', () => {
        it('should fetch attribute options', async () => {
            const mockOptions = { success: true, data: [{ id: '1', name: 'Red' }] };
            axiosInstance.get.mockResolvedValue({ data: mockOptions });

            const result = await attributeApi.getAttributeOptions('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/attributes/1/options');
            expect(result.success).toBe(true);
            expect(result.data).toEqual([{ id: '1', name: 'Red' }]);
        });
    });

    describe('createAttributeOption', () => {
        it('should create attribute option', async () => {
            const optionData = { option_name: 'Blue', value: 'blue' };
            const mockResponse = { success: true, data: { id: '2', ...optionData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.createAttributeOption('1', optionData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/attributes/1/options', optionData);
            expect(result.success).toBe(true);
        });
    });

    describe('updateAttributeOption', () => {
        it('should update attribute option', async () => {
            const optionData = { option_name: 'Dark Blue' };
            const mockResponse = { success: true, data: { id: '1', ...optionData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.updateAttributeOption('1', optionData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/attributes/options/1', optionData);
            expect(result.success).toBe(true);
        });
    });

    describe('deleteAttributeOption', () => {
        it('should delete attribute option', async () => {
            const mockResponse = { success: true, message: 'Deleted' };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await attributeApi.deleteAttributeOption('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/attributes/options/1');
            expect(result.success).toBe(true);
        });
    });
});

