import { describe, it, expect, vi, beforeEach } from 'vitest';
import productReducer, {
  fetchProducts,
  fetchProductDetail,
  setFilters,
  updateSingleProduct
} from './productSlice';
import * as productApi from '../../api/productApi';

// Mock productApi
vi.mock('../../api/productApi', () => ({
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

describe('productSlice', () => {
  const initialState = {
    items: [],
    currentProduct: null,
    highlightedProductId: null,
    pagination: {
      page: 1,
      limit: 20,
      total: 0,
      total_pages: 0,
    },
    filters: {
      search: '',
      category_id: null,
      product_type: null,
      status: null,
      is_active: null,
      brand: '',
      attributes: [],
      price_from: null,
      price_to: null,
      stock_from: null,
      stock_to: null,
      sort_by: 'p.created_at',
      order: 'desc',
    },
    loading: false,
    createLoading: false,
    updateLoading: false,
    deleteLoading: false,
    error: null,
    createSuccess: false,
    updateSuccess: false,
    deleteSuccess: false,
    categoryProducts: [],
    loadingCategoryProducts: false,
    errorCategoryProducts: null,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('reducers', () => {
    it('should handle initial state', () => {
      expect(productReducer(undefined, { type: 'unknown' })).toEqual(initialState);
    });

    it('should handle setFilters', () => {
      const newFilters = { search: 'test' };
      const nextState = productReducer(initialState, setFilters(newFilters));
      expect(nextState.filters.search).toBe('test');
    });

    it('should handle updateSingleProduct', () => {
        const stateWithProducts = {
            ...initialState,
            items: [{ id: 1, name: 'Old Name' }],
            currentProduct: { id: 1, name: 'Old Name' }
        };
        const updatedProduct = { id: 1, name: 'New Name' };
        const nextState = productReducer(stateWithProducts, updateSingleProduct(updatedProduct));

        expect(nextState.items[0].name).toBe('New Name');
        expect(nextState.currentProduct.name).toBe('New Name');
    });
  });

  describe('async thunks', () => {
    describe('fetchProducts', () => {
      it('should handle successful fetch', async () => {
        const mockResponse = {
          success: true,
          data: [{ id: 1, name: 'Product 1' }],
          pagination: { total: 1 }
        };
        productApi.getProductsWithVariants.mockResolvedValue(mockResponse);

        const dispatch = vi.fn();
        const thunk = fetchProducts({});

        await thunk(dispatch, () => {}, undefined);

        expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
            type: fetchProducts.fulfilled.type,
            payload: mockResponse
        }));
      });
    });

    describe('fetchProductDetail', () => {
        it('should handle successful fetch', async () => {
            const mockResponse = {
                success: true,
                data: { id: 1, name: 'Product 1', has_variants: '1' }
            };
            productApi.getProductDetail.mockResolvedValue(mockResponse);

            const dispatch = vi.fn();
            const thunk = fetchProductDetail(1);

            await thunk(dispatch, () => {}, undefined);

            expect(dispatch).toHaveBeenCalledWith(expect.objectContaining({
                type: fetchProductDetail.fulfilled.type,
                payload: mockResponse
            }));
        });
    });
  });
});
