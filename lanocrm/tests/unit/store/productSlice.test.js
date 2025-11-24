/**
 * Product Slice Unit Tests
 * @file tests/unit/store/productSlice.test.js
 * @description Comprehensive tests for product Redux slice
 */

import { configureStore } from '@reduxjs/toolkit';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import productReducer, {
  fetchProducts,
  fetchProductDetail,
  createProduct,
  updateProduct,
  deleteProduct,
  checkProductCode,
  uploadProductImage,
  importProducts,
  exportProducts,
  fetchProductsByCategory,
  setFilters,
  resetFilters,
  setCurrentProduct,
  clearCurrentProduct,
  resetSuccessFlags,
  clearError,
  setPagination,
  updateSingleProduct,
  setHighlightedProduct,
  clearHighlightedProduct,
} from '../../../src/store/slices/productSlice';

// Mock API
vi.mock('../../../src/api/productApi', () => ({
  getProductsWithVariants: vi.fn(),
  getProductDetail: vi.fn(),
  createProduct: vi.fn(),
  updateProduct: vi.fn(),
  deleteProduct: vi.fn(),
  checkProductCode: vi.fn(),
  uploadProductImage: vi.fn(),
  importProducts: vi.fn(),
  exportProducts: vi.fn(),
  getProducts: vi.fn(),
  downloadFile: vi.fn(),
}));

import * as productApi from '../../../src/api/productApi';

describe('Product Slice', () => {
  let store;

  beforeEach(() => {
    store = configureStore({
      reducer: {
        product: productReducer,
      },
    });
    vi.clearAllMocks();
  });

  describe('Initial State', () => {
    test('should return initial state', () => {
      const state = store.getState().product;
      expect(state.items).toEqual([]);
      expect(state.currentProduct).toBeNull();
      expect(state.highlightedProductId).toBeNull();
      expect(state.loading).toBe(false);
      expect(state.createLoading).toBe(false);
      expect(state.updateLoading).toBe(false);
      expect(state.deleteLoading).toBe(false);
      expect(state.error).toBeNull();
      expect(state.createSuccess).toBe(false);
      expect(state.updateSuccess).toBe(false);
      expect(state.deleteSuccess).toBe(false);
      expect(state.categoryProducts).toEqual([]);
      expect(state.loadingCategoryProducts).toBe(false);
      expect(state.errorCategoryProducts).toBeNull();
    });
  });

  describe('Synchronous Actions', () => {
    test('should set filters', () => {
      store.dispatch(setFilters({ search: 'test', category_id: 1 }));
      const state = store.getState().product;
      expect(state.filters.search).toBe('test');
      expect(state.filters.category_id).toBe(1);
    });

    test('should reset filters', () => {
      store.dispatch(setFilters({ search: 'test' }));
      store.dispatch(resetFilters());
      const state = store.getState().product;
      expect(state.filters.search).toBe('');
      expect(state.filters.category_id).toBeNull();
    });

    test('should set current product', () => {
      const product = { id: 1, name: 'Test Product' };
      store.dispatch(setCurrentProduct(product));
      const state = store.getState().product;
      expect(state.currentProduct).toEqual(product);
    });

    test('should clear current product', () => {
      store.dispatch(setCurrentProduct({ id: 1, name: 'Test' }));
      store.dispatch(clearCurrentProduct());
      const state = store.getState().product;
      expect(state.currentProduct).toBeNull();
    });

    test('should reset success flags', () => {
      store.dispatch({ type: 'product/createProduct/fulfilled', payload: { success: true } });
      let state = store.getState().product;
      expect(state.createSuccess).toBe(true);

      store.dispatch(resetSuccessFlags());
      state = store.getState().product;
      expect(state.createSuccess).toBe(false);
      expect(state.updateSuccess).toBe(false);
      expect(state.deleteSuccess).toBe(false);
    });

    test('should clear error', () => {
      store.dispatch({ type: 'product/fetchProducts/rejected', payload: 'Error' });
      let state = store.getState().product;
      expect(state.error).toBe('Error');

      store.dispatch(clearError());
      state = store.getState().product;
      expect(state.error).toBeNull();
    });

    test('should set pagination', () => {
      store.dispatch(setPagination({ page: 2, limit: 50 }));
      const state = store.getState().product;
      expect(state.pagination.page).toBe(2);
      expect(state.pagination.limit).toBe(50);
    });

    test('should update single product in items', () => {
      const initialProducts = [
        { id: 1, name: 'Product 1' },
        { id: 2, name: 'Product 2' },
      ];
      store.dispatch({ type: 'product/fetchProducts/fulfilled', payload: { success: true, data: initialProducts } });

      const updatedProduct = { id: 1, name: 'Updated Product 1' };
      store.dispatch(updateSingleProduct(updatedProduct));

      const state = store.getState().product;
      expect(state.items[0]).toEqual(updatedProduct);
      expect(state.items[1]).toEqual(initialProducts[1]);
    });

    test('should update current product when updating single product', () => {
      const product = { id: 1, name: 'Product 1' };
      const updatedProduct = { id: 1, name: 'Updated Product 1' };
      
      // First add product to items array and set as current
      store.dispatch({ type: 'product/fetchProducts/fulfilled', payload: { success: true, data: [product] } });
      store.dispatch(setCurrentProduct(product));

      // Now update it
      store.dispatch(updateSingleProduct(updatedProduct));

      const state = store.getState().product;
      expect(state.currentProduct).toEqual(updatedProduct);
    });

    test('should set highlighted product', () => {
      store.dispatch(setHighlightedProduct(123));
      const state = store.getState().product;
      expect(state.highlightedProductId).toBe(123);
    });

    test('should clear highlighted product', () => {
      store.dispatch(setHighlightedProduct(123));
      store.dispatch(clearHighlightedProduct());
      const state = store.getState().product;
      expect(state.highlightedProductId).toBeNull();
    });
  });

  describe('Async Thunks - fetchProducts', () => {
    test('should handle fetchProducts pending', () => {
      store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.loading).toBe(true);
      expect(state.error).toBeNull();
    });

    test('should handle fetchProducts fulfilled with success', async () => {
      const mockData = [{ id: 1, name: 'Product 1' }];
      const mockPagination = { page: 1, limit: 20, total: 1, total_pages: 1 };
      
      productApi.getProductsWithVariants.mockResolvedValue({
        success: true,
        data: mockData,
        pagination: mockPagination,
      });

      await store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.loading).toBe(false);
      expect(state.items).toEqual(mockData);
      expect(state.pagination).toEqual(mockPagination);
    });

    test('should handle fetchProducts fulfilled with failure', async () => {
      productApi.getProductsWithVariants.mockResolvedValue({
        success: false,
        message: 'API Error',
      });

      await store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.loading).toBe(false);
      expect(state.error).toBe('API Error');
    });

    test('should handle fetchProducts rejected', async () => {
      productApi.getProductsWithVariants.mockRejectedValue(new Error('Network error'));

      await store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('Async Thunks - fetchProductDetail', () => {
    test('should handle fetchProductDetail pending', () => {
      store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.loading).toBe(true);
      expect(state.error).toBeNull();
    });

    test('should handle fetchProductDetail fulfilled with success', async () => {
      const mockProduct = {
        id: 1,
        name: 'Product 1',
        variants: [{ id: 1, sku: 'VAR1' }],
        has_variants: '1',
      };
      
      productApi.getProductDetail.mockResolvedValue({
        success: true,
        data: mockProduct,
      });

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.loading).toBe(false);
      expect(state.currentProduct).toEqual({
        ...mockProduct,
        has_variants: 1, // Should be converted to number
      });
    });

    test('should handle fetchProductDetail with variants_v2', async () => {
      const mockProduct = {
        id: 1,
        name: 'Product 1',
        variants_v2: [{ id: 1, sku: 'VAR1' }],
        has_variants: true,
      };
      
      productApi.getProductDetail.mockResolvedValue({
        success: true,
        data: mockProduct,
      });

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.currentProduct.variants).toEqual(mockProduct.variants_v2);
    });

    test('should handle fetchProductDetail with no variants', async () => {
      const mockProduct = {
        id: 1,
        name: 'Product 1',
        has_variants: undefined,
      };
      
      productApi.getProductDetail.mockResolvedValue({
        success: true,
        data: mockProduct,
      });

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.currentProduct.variants).toEqual([]);
      expect(state.currentProduct.has_variants).toBe(0);
    });

    test('should handle fetchProductDetail rejected', async () => {
      productApi.getProductDetail.mockRejectedValue(new Error('Network error'));

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.loading).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('Async Thunks - createProduct', () => {
    test('should handle createProduct pending', () => {
      store.dispatch(createProduct({ name: 'New Product' }));
      const state = store.getState().product;
      expect(state.createLoading).toBe(true);
      expect(state.createSuccess).toBe(false);
      expect(state.error).toBeNull();
    });

    test('should handle createProduct fulfilled with success', async () => {
      productApi.createProduct.mockResolvedValue({ success: true });

      await store.dispatch(createProduct({ name: 'New Product' }));
      const state = store.getState().product;
      expect(state.createLoading).toBe(false);
      expect(state.createSuccess).toBe(true);
    });

    test('should handle createProduct fulfilled with failure', async () => {
      productApi.createProduct.mockResolvedValue({
        success: false,
        message: 'Validation error',
      });

      await store.dispatch(createProduct({ name: 'New Product' }));
      const state = store.getState().product;
      expect(state.createLoading).toBe(false);
      expect(state.createSuccess).toBe(false);
      expect(state.error).toBe('Validation error');
    });

    test('should handle createProduct rejected', async () => {
      productApi.createProduct.mockRejectedValue(new Error('Network error'));

      await store.dispatch(createProduct({ name: 'New Product' }));
      const state = store.getState().product;
      expect(state.createLoading).toBe(false);
      expect(state.createSuccess).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('Async Thunks - updateProduct', () => {
    test('should handle updateProduct pending', () => {
      store.dispatch(updateProduct({ id: 1, data: { name: 'Updated' } }));
      const state = store.getState().product;
      expect(state.updateLoading).toBe(true);
      expect(state.updateSuccess).toBe(false);
      expect(state.error).toBeNull();
    });

    test('should handle updateProduct fulfilled with success', async () => {
      productApi.updateProduct.mockResolvedValue({ success: true });

      await store.dispatch(updateProduct({ id: 1, data: { name: 'Updated' } }));
      const state = store.getState().product;
      expect(state.updateLoading).toBe(false);
      expect(state.updateSuccess).toBe(true);
    });

    test('should handle updateProduct rejected', async () => {
      productApi.updateProduct.mockRejectedValue(new Error('Network error'));

      await store.dispatch(updateProduct({ id: 1, data: { name: 'Updated' } }));
      const state = store.getState().product;
      expect(state.updateLoading).toBe(false);
      expect(state.updateSuccess).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('Async Thunks - deleteProduct', () => {
    test('should handle deleteProduct pending', () => {
      store.dispatch(deleteProduct(1));
      const state = store.getState().product;
      expect(state.deleteLoading).toBe(true);
      expect(state.deleteSuccess).toBe(false);
      expect(state.error).toBeNull();
    });

    test('should handle deleteProduct fulfilled with success', async () => {
      productApi.deleteProduct.mockResolvedValue({ success: true });

      await store.dispatch(deleteProduct(1));
      const state = store.getState().product;
      expect(state.deleteLoading).toBe(false);
      expect(state.deleteSuccess).toBe(true);
    });

    test('should handle deleteProduct rejected', async () => {
      productApi.deleteProduct.mockRejectedValue(new Error('Network error'));

      await store.dispatch(deleteProduct(1));
      const state = store.getState().product;
      expect(state.deleteLoading).toBe(false);
      expect(state.deleteSuccess).toBe(false);
      expect(state.error).toBe('Network error');
    });
  });

  describe('Async Thunks - checkProductCode', () => {
    test('should handle checkProductCode fulfilled', async () => {
      productApi.checkProductCode.mockResolvedValue({
        success: true,
        exists: false,
      });

      await store.dispatch(checkProductCode({ code: 'TEST001' }));
      expect(productApi.checkProductCode).toHaveBeenCalledWith('TEST001', undefined);
    });

    test('should handle checkProductCode with excludeId', async () => {
      productApi.checkProductCode.mockResolvedValue({
        success: true,
        exists: false,
      });

      await store.dispatch(checkProductCode({ code: 'TEST001', excludeId: 1 }));
      expect(productApi.checkProductCode).toHaveBeenCalledWith('TEST001', 1);
    });
  });

  describe('Async Thunks - uploadProductImage', () => {
    test('should handle uploadProductImage fulfilled', async () => {
      const mockFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      productApi.uploadProductImage.mockResolvedValue({
        success: true,
        data: { url: 'http://example.com/image.jpg' },
      });

      await store.dispatch(uploadProductImage(mockFile));
      expect(productApi.uploadProductImage).toHaveBeenCalledWith(mockFile);
    });
  });

  describe('Async Thunks - importProducts', () => {
    test('should handle importProducts fulfilled', async () => {
      const mockFile = new File(['test'], 'products.csv', { type: 'text/csv' });
      productApi.importProducts.mockResolvedValue({
        success: true,
        imported: 10,
      });

      await store.dispatch(importProducts(mockFile));
      expect(productApi.importProducts).toHaveBeenCalledWith(mockFile);
    });
  });

  describe('Async Thunks - exportProducts', () => {
    test('should handle exportProducts fulfilled', async () => {
      const mockBlob = new Blob(['test'], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      productApi.exportProducts.mockResolvedValue(mockBlob);

      await store.dispatch(exportProducts({}));
      expect(productApi.exportProducts).toHaveBeenCalledWith({});
      expect(productApi.downloadFile).toHaveBeenCalledWith(mockBlob, 'products.xlsx');
    });
  });

  describe('Async Thunks - fetchProductsByCategory', () => {
    test('should handle fetchProductsByCategory pending', () => {
      store.dispatch(fetchProductsByCategory({ category_id: 1 }));
      const state = store.getState().product;
      expect(state.loadingCategoryProducts).toBe(true);
      expect(state.errorCategoryProducts).toBeNull();
    });

    test('should handle fetchProductsByCategory fulfilled with success', async () => {
      const mockData = [{ id: 1, name: 'Product 1' }];
      productApi.getProducts.mockResolvedValue({
        success: true,
        data: mockData,
      });

      await store.dispatch(fetchProductsByCategory({ category_id: 1 }));
      const state = store.getState().product;
      expect(state.loadingCategoryProducts).toBe(false);
      expect(state.categoryProducts).toEqual(mockData);
    });

    test('should handle fetchProductsByCategory fulfilled with failure', async () => {
      productApi.getProducts.mockResolvedValue({
        success: false,
        message: 'Category error',
      });

      await store.dispatch(fetchProductsByCategory({ category_id: 1 }));
      const state = store.getState().product;
      expect(state.loadingCategoryProducts).toBe(false);
      expect(state.errorCategoryProducts).toBe('Category error');
    });

    test('should handle fetchProductsByCategory rejected', async () => {
      productApi.getProducts.mockRejectedValue(new Error('Network error'));

      await store.dispatch(fetchProductsByCategory({ category_id: 1 }));
      const state = store.getState().product;
      expect(state.loadingCategoryProducts).toBe(false);
      expect(state.errorCategoryProducts).toBe('Network error');
    });
  });

  describe('Edge Cases', () => {
    test('should handle empty data array in fetchProducts', async () => {
      productApi.getProductsWithVariants.mockResolvedValue({
        success: true,
        data: null, // null instead of array
      });

      await store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.items).toEqual([]);
    });

    test('should handle non-array data in fetchProducts', async () => {
      productApi.getProductsWithVariants.mockResolvedValue({
        success: true,
        data: 'not an array', // string instead of array
      });

      await store.dispatch(fetchProducts());
      const state = store.getState().product;
      expect(state.items).toEqual([]);
    });

    test('should handle string has_variants conversion', async () => {
      const mockProduct = {
        id: 1,
        name: 'Product 1',
        has_variants: 'invalid', // invalid string
      };
      
      productApi.getProductDetail.mockResolvedValue({
        success: true,
        data: mockProduct,
      });

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.currentProduct.has_variants).toBe(0); // Should default to 0
    });

    test('should handle NaN has_variants conversion', async () => {
      const mockProduct = {
        id: 1,
        name: 'Product 1',
        has_variants: NaN, // NaN value
      };
      
      productApi.getProductDetail.mockResolvedValue({
        success: true,
        data: mockProduct,
      });

      await store.dispatch(fetchProductDetail(1));
      const state = store.getState().product;
      expect(state.currentProduct.has_variants).toBe(0); // Should default to 0
    });
  });

  describe('State Persistence', () => {
    test('should maintain state consistency across multiple actions', () => {
      // Set initial state
      store.dispatch(setFilters({ search: 'test' }));
      store.dispatch(setCurrentProduct({ id: 1, name: 'Test' }));
      store.dispatch(setHighlightedProduct(123));

      let state = store.getState().product;
      expect(state.filters.search).toBe('test');
      expect(state.currentProduct.id).toBe(1);
      expect(state.highlightedProductId).toBe(123);

      // Reset some parts
      store.dispatch(resetFilters());
      store.dispatch(clearCurrentProduct());

      state = store.getState().product;
      expect(state.filters.search).toBe(''); // Reset
      expect(state.currentProduct).toBeNull(); // Cleared
      expect(state.highlightedProductId).toBe(123); // Should remain
    });
  });
});
