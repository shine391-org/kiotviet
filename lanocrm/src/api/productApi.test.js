import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as productApi from './productApi';
import axiosInstance from './axios';

vi.mock('./axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  }
}));

describe('productApi', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('getProducts', () => {
    it('should fetch products with params', async () => {
      const mockData = { success: true, data: [] };
      axiosInstance.get.mockResolvedValue({ data: mockData });

      const params = { page: 1, limit: 10, search: 'test' };
      const result = await productApi.getProducts(params);

      expect(axiosInstance.get).toHaveBeenCalledWith('/products', expect.objectContaining({
        params: expect.objectContaining({
          search: 'test',
          page: 1,
          limit: 10
        })
      }));
      expect(result).toEqual(mockData);
    });

    it('should handle errors', async () => {
      const error = new Error('Network error');
      axiosInstance.get.mockRejectedValue(error);

      await expect(productApi.getProducts()).rejects.toThrow('Network error');
    });
  });

  describe('getProductDetail', () => {
    it('should fetch product detail', async () => {
      const mockData = { success: true, data: { id: 1 } };
      axiosInstance.get.mockResolvedValue({ data: mockData });

      const result = await productApi.getProductDetail(1);

      expect(axiosInstance.get).toHaveBeenCalledWith('/products/1', undefined);
      expect(result).toEqual(mockData);
    });

    it('should throw error for invalid ID', async () => {
      await expect(productApi.getProductDetail('invalid')).rejects.toThrow('Invalid product ID');
    });

    it('should forward axios config when provided', async () => {
      const config = { params: { price_list_id: 5 } };
      const mockData = { success: true, data: { id: 2 } };
      axiosInstance.get.mockResolvedValue({ data: mockData });

      const result = await productApi.getProductDetail(2, config);

      expect(axiosInstance.get).toHaveBeenCalledWith('/products/2', config);
      expect(result).toEqual(mockData);
    });

    it('should handle 404 gracefully', async () => {
         const error = { response: { status: 404 } };
         axiosInstance.get.mockRejectedValue(error);

         const result = await productApi.getProductDetail(1);
         expect(result.success).toBe(false);
         expect(result.message).toBe('Product not found');
    });
  });

  describe('createProduct', () => {
    it('should create product', async () => {
      const productData = {
        code: 'P001',
        name: 'Product',
        category_id: [1],
        product_type: 'single',
        unit: 'pcs',
        selling_price: 100
      };
      const mockResponse = { success: true, data: { id: 1 } };
      axiosInstance.post.mockResolvedValue({ data: mockResponse });

      const result = await productApi.createProduct(productData);

      expect(axiosInstance.post).toHaveBeenCalledWith('/products', expect.any(Object));
      expect(result).toEqual(mockResponse);
    });

    it('should validate required fields', async () => {
      const invalidData = { name: 'Product' }; // Missing code, etc.
      await expect(productApi.createProduct(invalidData)).rejects.toThrow('Thiếu trường bắt buộc');
    });
  });

  describe('updateProduct', () => {
    it('should update product', async () => {
      const productData = { name: 'Updated' };
      const mockResponse = { success: true };
      axiosInstance.put.mockResolvedValue({ data: mockResponse });

      const result = await productApi.updateProduct(1, productData);

      expect(axiosInstance.put).toHaveBeenCalledWith('/products/1', productData);
      expect(result).toEqual(mockResponse);
    });
  });

  describe('deleteProduct', () => {
    it('should delete product if confirmed', async () => {
      const mockResponse = { success: true };
      axiosInstance.delete.mockResolvedValue({ data: mockResponse });

      const result = await productApi.deleteProduct(1, true);

      expect(axiosInstance.delete).toHaveBeenCalledWith('/products/1');
      expect(result).toEqual(mockResponse);
    });

    it('should throw if not confirmed', async () => {
      await expect(productApi.deleteProduct(1)).rejects.toThrow('Delete action must be confirmed');
    });
  });
});
