import { describe, it, expect, vi, beforeEach } from 'vitest';
import categoryApi from './categoryApi';

vi.mock('./axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

import axiosInstance from './axios';

describe('categoryApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('getCategories', () => {
        it('should fetch categories', async () => {
            const mockData = { data: [{ id: '1', name: 'Category 1' }] };
            axiosInstance.get.mockResolvedValue({ data: mockData });

            const result = await categoryApi.getCategories();

            expect(axiosInstance.get).toHaveBeenCalledWith('/product-categories', { params: {} });
            expect(result).toEqual(mockData);
        });
    });

    describe('getCategoryTree', () => {
        it('should fetch category tree', async () => {
            const mockTree = { success: true, data: [{ id: '1', name: 'Root', children: [] }] };
            axiosInstance.get.mockResolvedValue({ data: mockTree });

            const result = await categoryApi.getCategoryTree();

            expect(axiosInstance.get).toHaveBeenCalledWith('/product-categories', { params: { view: 'tree' } });
            expect(result).toEqual(mockTree);
        });
    });

    describe('getCategoryDetail', () => {
        it('should fetch category by id', async () => {
            const mockCategory = { data: { id: '1', name: 'Category 1' } };
            axiosInstance.get.mockResolvedValue({ data: mockCategory });

            const result = await categoryApi.getCategoryDetail('1');

            expect(axiosInstance.get).toHaveBeenCalledWith('/product-categories/1');
            expect(result).toEqual(mockCategory);
        });
    });

    describe('createCategory', () => {
        it('should create new category', async () => {
            const categoryData = { name: 'New Category', parent_id: null };
            const mockResponse = { data: { id: '2', ...categoryData } };
            axiosInstance.post.mockResolvedValue({ data: mockResponse });

            const result = await categoryApi.createCategory(categoryData);

            expect(axiosInstance.post).toHaveBeenCalledWith('/product-categories', categoryData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('updateCategory', () => {
        it('should update category', async () => {
            const categoryData = { name: 'Updated Category' };
            const mockResponse = { data: { id: '1', ...categoryData } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await categoryApi.updateCategory('1', categoryData);

            expect(axiosInstance.put).toHaveBeenCalledWith('/product-categories/1', categoryData);
            expect(result).toEqual(mockResponse);
        });
    });

    describe('deleteCategory', () => {
        it('should delete category', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await categoryApi.deleteCategory('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/product-categories/1');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('hardDeleteCategory', () => {
        it('should hard delete category', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.delete.mockResolvedValue({ data: mockResponse });

            const result = await categoryApi.hardDeleteCategory('1');

            expect(axiosInstance.delete).toHaveBeenCalledWith('/product-categories/1/hard');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('restoreCategory', () => {
        it('should restore deleted category', async () => {
            const mockResponse = { data: { success: true } };
            axiosInstance.put.mockResolvedValue({ data: mockResponse });

            const result = await categoryApi.restoreCategory('1');

            expect(axiosInstance.put).toHaveBeenCalledWith('/product-categories/1/restore');
            expect(result).toEqual(mockResponse);
        });
    });

    describe('flattenCategoryTree', () => {
        it('should flatten category tree with levels', () => {
            const tree = [
                {
                    id: '1', name: 'Root', children: [
                        { id: '2', name: 'Child 1', children: [] },
                        { id: '3', name: 'Child 2', children: [] }
                    ]
                }
            ];

            const result = categoryApi.flattenCategoryTree(tree);

            expect(result).toHaveLength(3);
            expect(result[0].level).toBe(0);
            expect(result[1].level).toBe(1);
            expect(result[2].level).toBe(1);
        });
    });
});

