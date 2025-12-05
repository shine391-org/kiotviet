/**
 * Product Redux Slice - FULL VERSION with Real API
 * @file src/store/slices/productSlice.js
 * @description State management for Products module with backend integration
 */

import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import * as productApi from '../../api/productApi';


/**
 * Initial State
 */
const initialState = {
  // Products list
  items: [],  // ✅ UPDATED: 'products' → 'items' (giống pattern userSlice)
  currentProduct: null,
  // ✅ NEW: Highlighted product for scroll & animation
  highlightedProductId: null,
  
  // Pagination
  pagination: {
    page: 1,
    limit: 20,
    total: 0,
    total_pages: 0,
  },
  
  // Filters
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
  
  // Loading states
  loading: false,
  createLoading: false,
  updateLoading: false,
  deleteLoading: false,
  
  // Error states
  error: null,
  
  // Success flags
  createSuccess: false,
  updateSuccess: false,
  deleteSuccess: false,

  categoryProducts: [], // sản phẩm riêng của category hiện tại mở drawer
  loadingCategoryProducts: false,
  errorCategoryProducts: null,
};


/**
 * Async Thunks (API calls) - REAL API INTEGRATION
 */


/**
 * Fetch products list with filters & variants
 */
export const fetchProducts = createAsyncThunk(
  'product/fetchProducts',
  async (params, { rejectWithValue }) => {
    try {
      // ✅ UPDATED: Now includes variants data
      const response = await productApi.getProductsWithVariants(params);

      // Enrich missing images to avoid placeholder in list
      const data = Array.isArray(response?.data) ? response.data : [];
      const enriched = await Promise.all(
        data.map(async (p) => {
          if (!p.image && p.id) {
            try {
              const imgs = await productApi.getProductImages(p.id);
              if (imgs.success && Array.isArray(imgs.data) && imgs.data.length > 0) {
                const first = imgs.data[0];
                return {
                  ...p,
                  image: first.image_url || first.url || first.path || first.image_path || p.image,
                  images: imgs.data,
                };
              }
            } catch (e) {
              // ignore enrich failure
            }
          }
          return p;
        })
      );

      return { ...response, data: enriched };
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể tải danh sách sản phẩm'
      );
    }
  }
);


/**
 * Fetch product detail by ID
 */
export const fetchProductDetail = createAsyncThunk(
  'product/fetchProductDetail',
  async (id, { rejectWithValue }) => {
    try {
      const response = await productApi.getProductDetail(id);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể tải thông tin sản phẩm'
      );
    }
  }
);


/**
 * Create new product
 */
export const createProduct = createAsyncThunk(
  'product/createProduct',
  async (data, { rejectWithValue }) => {
    try {
      const response = await productApi.createProduct(data);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể tạo sản phẩm'
      );
    }
  }
);


/**
 * Update existing product
 */
export const updateProduct = createAsyncThunk(
  'product/updateProduct',
  async ({ id, data }, { rejectWithValue }) => {
    try {
      const response = await productApi.updateProduct(id, data);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể cập nhật sản phẩm'
      );
    }
  }
);


/**
 * Delete product
 */
export const deleteProduct = createAsyncThunk(
  'product/deleteProduct',
  async (id, { rejectWithValue }) => {
    try {
      const response = await productApi.deleteProduct(id);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể xóa sản phẩm'
      );
    }
  }
);


/**
 * Check if product code exists
 */
export const checkProductCode = createAsyncThunk(
  'product/checkProductCode',
  async ({ code, excludeId }, { rejectWithValue }) => {
    try {
      const response = await productApi.checkProductCode(code, excludeId);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể kiểm tra mã sản phẩm'
      );
    }
  }
);


/**
 * Upload product image
 */
export const uploadProductImage = createAsyncThunk(
  'product/uploadProductImage',
  async (file, { rejectWithValue }) => {
    try {
      const response = await productApi.uploadProductImage(file);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể upload ảnh'
      );
    }
  }
);


/**
 * Import products from CSV/Excel
 */
export const importProducts = createAsyncThunk(
  'product/importProducts',
  async (file, { rejectWithValue }) => {
    try {
      const response = await productApi.importProducts(file);
      return response;
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể import sản phẩm'
      );
    }
  }
);


/**
 * Export products to CSV/Excel
 */
export const exportProducts = createAsyncThunk(
  'product/exportProducts',
  async (params, { rejectWithValue }) => {
    try {
      const blob = await productApi.exportProducts(params);
      productApi.downloadFile(blob, 'products.xlsx');
      return { success: true };
    } catch (error) {
      return rejectWithValue(
        error.response?.data?.message || 
        error.message || 
        'Không thể export sản phẩm'
      );
    }
  }
);

// Thunk lấy sản phẩm theo category id (kèm filter đã soft delete, active, variant etc)
export const fetchProductsByCategory = createAsyncThunk(
  'product/fetchProductsByCategory',
  async ({ category_id }, { rejectWithValue }) => {
    try {
      const response = await productApi.getProducts({ category_id });
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message || 'Không thể tải danh sách sản phẩm');
    }
  }
);

/**
 * Product Slice
 */
const productSlice = createSlice({
  name: 'product',
  initialState,
  reducers: {
    // Set filters
    setFilters: (state, action) => {
      state.filters = {
        ...state.filters,
        ...action.payload,
      };
    },
    
    // Reset filters
    resetFilters: (state) => {
      state.filters = initialState.filters;
    },
    
    // Set current product
    setCurrentProduct: (state, action) => {
      state.currentProduct = action.payload;
    },
    
    // Clear current product
    clearCurrentProduct: (state) => {
      state.currentProduct = null;
    },
    
    // Reset success flags
    resetSuccessFlags: (state) => {
      state.createSuccess = false;
      state.updateSuccess = false;
      state.deleteSuccess = false;
    },
    
    // Clear error
    clearError: (state) => {
      state.error = null;
    },
    
    // Set pagination
    setPagination: (state, action) => {
      state.pagination = {
        ...state.pagination,
        ...action.payload,
      };
    },
    // ✅ NEW: Update single product in items array (after delete variant)
    updateSingleProduct: (state, action) => {
      const updatedProduct = action.payload;
      const index = state.items.findIndex(p => p.id === updatedProduct.id);
      
      if (index !== -1) {
        // Replace old product with new one
        state.items[index] = updatedProduct;
        
        // Also update currentProduct if it's the same
        if (state.currentProduct?.id === updatedProduct.id) {
          state.currentProduct = updatedProduct;
        }
      }
    },
    
    // ✅ NEW: Set highlighted product (for scroll & animation)
    setHighlightedProduct: (state, action) => {
      state.highlightedProductId = action.payload;
    },
    
    // ✅ NEW: Clear highlighted product
    clearHighlightedProduct: (state) => {
      state.highlightedProductId = null;
    },
  },
  extraReducers: (builder) => {
    // Fetch products
    builder
      .addCase(fetchProducts.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchProducts.fulfilled, (state, action) => {
        state.loading = false;
        if (action.payload && action.payload.success) {
          if (Array.isArray(action.payload.data)) {
            state.items = action.payload.data;  // ✅ UPDATED: state.items
          } else {
            state.items = [];  // ✅ UPDATED: state.items
          }
          
          // Pagination
          if (action.payload.pagination) {
            state.pagination = action.payload.pagination;
          }
        } else {
          state.error = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })
      .addCase(fetchProducts.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || 'Lỗi kết nối';
      });
    
    // Fetch product detail
    builder
      .addCase(fetchProductDetail.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchProductDetail.fulfilled, (state, action) => {
        state.loading = false;
      
        if (action.payload && action.payload.success) {
          const data = action.payload.data;
      
          data.variants = Array.isArray(data.variants) ? data.variants
                        : Array.isArray(data.variants_v2) ? data.variants_v2
                        : [];
          
          // CHUẨN HÓA GIÁ TRỊ - KHÔNG ĐỔI TÊN TRƯỜG
          if (data.has_variants !== undefined) {
            if (typeof data.has_variants === 'string') {
              data.has_variants = parseInt(data.has_variants, 10);
            }
            // Nếu vẫn không là số, ép về 0
            if (isNaN(data.has_variants)) {
              data.has_variants = 0;
            }
          } else {
            // Không có trường has_variants từ API
            data.has_variants = 0;
          }
      
          state.currentProduct = data;
        } else {
          state.error = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })      
      
      .addCase(fetchProductDetail.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || 'Lỗi kết nối';
      });
    
    // Create product
    builder
      .addCase(createProduct.pending, (state) => {
        state.createLoading = true;
        state.createSuccess = false;
        state.error = null;
      })
      .addCase(createProduct.fulfilled, (state, action) => {
        state.createLoading = false;
        
        if (action.payload && action.payload.success) {
          state.createSuccess = true;
        } else {
          state.error = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })
      .addCase(createProduct.rejected, (state, action) => {
        state.createLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      });
    
    // Update product
    builder
      .addCase(updateProduct.pending, (state) => {
        state.updateLoading = true;
        state.updateSuccess = false;
        state.error = null;
      })
      .addCase(updateProduct.fulfilled, (state, action) => {
        state.updateLoading = false;
        
        if (action.payload && action.payload.success) {
          state.updateSuccess = true;
        } else {
          state.error = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })
      .addCase(updateProduct.rejected, (state, action) => {
        state.updateLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      });
    
    // Delete product
    builder
      .addCase(deleteProduct.pending, (state) => {
        state.deleteLoading = true;
        state.deleteSuccess = false;
        state.error = null;
      })
      .addCase(deleteProduct.fulfilled, (state, action) => {
        state.deleteLoading = false;
        
        if (action.payload && action.payload.success) {
          state.deleteSuccess = true;
        } else {
          state.error = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })
      .addCase(deleteProduct.rejected, (state, action) => {
        state.deleteLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      });

    // get product by category  
    builder
      .addCase(fetchProductsByCategory.pending, (state) => {
        state.loadingCategoryProducts = true;
        state.errorCategoryProducts = null;
      })
      .addCase(fetchProductsByCategory.fulfilled, (state, action) => {
        state.loadingCategoryProducts = false;
        if (action.payload && action.payload.success) {
          state.categoryProducts = action.payload.data || [];
        } else {
          state.errorCategoryProducts = action.payload?.message || action.payload || 'Lỗi không xác định';
        }
      })
      .addCase(fetchProductsByCategory.rejected, (state, action) => {
        state.loadingCategoryProducts = false;
        state.errorCategoryProducts = action.payload || 'Lỗi kết nối';
      });
  },
  
});


// Export actions
export const {
  setFilters,
  resetFilters,
  setCurrentProduct,
  clearCurrentProduct,
  resetSuccessFlags,
  clearError,
  setPagination,
  updateSingleProduct,        // ✅ NEW
  setHighlightedProduct,       // ✅ NEW
  clearHighlightedProduct,     // ✅ NEW
} = productSlice.actions;


// Export reducer
export default productSlice.reducer;
