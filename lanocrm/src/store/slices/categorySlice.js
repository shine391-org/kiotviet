import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import * as categoryApi from '../../api/categoryApi';

const initialState = {
  categories: [],
  categoryTree: [],
  productCountMap: {},  // id => số lượng sp trong category
  currentCategory: null,
  categoryProducts: [],
  categoryProductsLoading: false,
  
  loading: false,
  createLoading: false,
  updateLoading: false,
  deleteLoading: false,
  
  error: null,
  
  createSuccess: false,
  updateSuccess: false,
  deleteSuccess: false,
};

/**
 * Giúp cập nhật map id category và số sản phẩm cho từng category
 */
const buildProductCountMap = (tree) => {
  const map = {};
  function traverse(nodes) {
    nodes.forEach(node => {
      map[node.id] = node.product_count || 0;
      if (node.children && node.children.length > 0) {
        traverse(node.children);
      }
    });
  }
  traverse(tree || []);
  return map;
};

export const fetchCategories = createAsyncThunk('category/fetchCategories', async (params, { rejectWithValue }) => {
  try {
    const response = await categoryApi.getCategories(params);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể tải danh sách nhóm hàng');
  }
});

export const fetchCategoryTree = createAsyncThunk('category/fetchCategoryTree', async (arg, { rejectWithValue }) => {
  try {
    const response = await categoryApi.getCategoryTree(arg?.include_deleted);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể tải cây nhóm hàng');
  }
});

export const fetchCategoryDetail = createAsyncThunk('category/fetchCategoryDetail', async (id, { rejectWithValue }) => {
  try {
    const response = await categoryApi.getCategoryDetail(id);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể tải thông tin nhóm hàng');
  }
});

export const createCategory = createAsyncThunk('category/createCategory', async (data, { rejectWithValue }) => {
  try {
    const response = await categoryApi.createCategory(data);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể tạo nhóm hàng');
  }
});

export const updateCategory = createAsyncThunk('category/updateCategory', async ({ id, data }, { rejectWithValue }) => {
  try {
    const response = await categoryApi.updateCategory(id, data);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể cập nhật nhóm hàng');
  }
});

export const hardDeleteCategoryThunk = createAsyncThunk(
  'category/hardDelete',
  async (id, { rejectWithValue }) => {
    try {
      const res = await categoryApi.hardDeleteCategory(id);
      return res;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const restoreCategoryThunk = createAsyncThunk(
  'category/restore',
  async (id, { rejectWithValue }) => {
    try {
      const res = await categoryApi.restoreCategory(id);
      return res;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const fetchCategoryProducts = createAsyncThunk(
  'category/fetchCategoryProducts',
  async (category_id, { rejectWithValue }) => {
    try {
      const response = await categoryApi.getCategoryProducts(category_id);
      return response;
    } catch (error) {
      return rejectWithValue(error.response?.data?.message || error.message);
    }
  }
);

export const deleteCategory = createAsyncThunk('category/deleteCategory', async (id, { rejectWithValue }) => {
  try {
    const response = await categoryApi.deleteCategory(id);
    return response;
  } catch (error) {
    return rejectWithValue(error.response?.data?.message || error.message || 'Không thể xóa nhóm hàng');
  }
});

const categorySlice = createSlice({
  name: 'category',
  initialState,
  reducers: {
    setCurrentCategory: (state, action) => {
      state.currentCategory = action.payload;
    },
    clearCurrentCategory: (state) => {
      state.currentCategory = null;
    },
    resetSuccessFlags: (state) => {
      state.createSuccess = false;
      state.updateSuccess = false;
      state.deleteSuccess = false;
    },
    clearError: (state) => {
      state.error = null;
    }
  },
  extraReducers: (builder) => {
    builder
      // fetchCategories
      .addCase(fetchCategories.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCategories.fulfilled, (state, action) => {
        state.loading = false;
        if (action.payload.success) {
          state.categories = action.payload.data || [];
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(fetchCategories.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })

      // fetchCategoryTree
      .addCase(fetchCategoryTree.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCategoryTree.fulfilled, (state, action) => {
        state.loading = false;
        if (action.payload.success) {
          state.categoryTree = action.payload.data || [];
          state.productCountMap = buildProductCountMap(action.payload.data);
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(fetchCategoryTree.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })

      // fetchCategoryDetail
      .addCase(fetchCategoryDetail.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchCategoryDetail.fulfilled, (state, action) => {
        state.loading = false;
        if (action.payload.success) {
          state.currentCategory = action.payload.data;
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(fetchCategoryDetail.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })

      // createCategory
      .addCase(createCategory.pending, (state) => {
        state.createLoading = true;
        state.createSuccess = false;
        state.error = null;
      })
      .addCase(createCategory.fulfilled, (state, action) => {
        state.createLoading = false;
        if (action.payload.success) {
          state.createSuccess = true;
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(createCategory.rejected, (state, action) => {
        state.createLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })

      // updateCategory
      .addCase(updateCategory.pending, (state) => {
        state.updateLoading = true;
        state.updateSuccess = false;
        state.error = null;
      })
      .addCase(updateCategory.fulfilled, (state, action) => {
        state.updateLoading = false;
        if (action.payload.success) {
          state.updateSuccess = true;
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(updateCategory.rejected, (state, action) => {
        state.updateLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })

      // deleteCategory
      .addCase(deleteCategory.pending, (state) => {
        state.deleteLoading = true;
        state.deleteSuccess = false;
        state.error = null;
      })
      .addCase(deleteCategory.fulfilled, (state, action) => {
        state.deleteLoading = false;
        if (action.payload.success) {
          state.deleteSuccess = true;
          // Update lại cây và product_count nếu có trả về dữ liệu mới
          if (action.payload.data) {
            state.categoryTree = action.payload.data;
            state.productCountMap = buildProductCountMap(action.payload.data);
          }
        } else {
          state.error = action.payload.message || 'Lỗi không xác định';
        }
      })
      .addCase(deleteCategory.rejected, (state, action) => {
        state.deleteLoading = false;
        state.error = action.payload || 'Lỗi kết nối';
      })
      .addCase(fetchCategoryProducts.pending, (state) => {
        state.categoryProductsLoading = true;
      })
      .addCase(fetchCategoryProducts.fulfilled, (state, action) => {
        state.categoryProductsLoading = false;
        if (action.payload.success) {
          state.categoryProducts = action.payload.data || [];
        }
      })
      .addCase(fetchCategoryProducts.rejected, (state) => {
        state.categoryProductsLoading = false;
        state.categoryProducts = [];
      });    
  }
});

export const {
  setCurrentCategory,
  clearCurrentCategory,
  resetSuccessFlags,
  clearError,
} = categorySlice.actions;

export default categorySlice.reducer;