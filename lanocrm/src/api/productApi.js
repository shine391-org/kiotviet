/**
 * Product API Service
 * @file src/api/productApi.js
 * @description API calls for Products module
 * @backend https://banhang.tuidanam.org/backend-ci/api
 */

import axiosInstance from './axios';

/**
 * API Endpoints
 */
const ENDPOINTS = {
  BASE: '/products',
  DETAIL: (id) => `/products/${id}`,
  CHECK_CODE: '/products/check-code',
  UPLOAD_IMAGE: '/products/upload-image',
  IMPORT: '/products/import',
  EXPORT: '/products/export',
  ANALYTICS: (id) => `/products/${id}/analytics`,
  // Product Images
  PRODUCT_IMAGES: (id) => `/products/${id}/images`,
  PRODUCT_UPLOAD: '/products/upload',
  PRODUCT_SET_PRIMARY: (id) => `/products/images/${id}/set-primary`,
  PRODUCT_DELETE_IMAGE: (id) => `/products/images/${id}`,
  
  // ✅ MEDIA LIBRARY ENDPOINTS (NEW)
  MEDIA_LIBRARY: '/products/media/library',
  MEDIA_BY_DATE: '/products/media/by-date',
  MEDIA_SEARCH_SKU: '/products/media/search-sku',
  IMAGES_ATTACH_MULTIPLE: (id) => `/products/${id}/images/attach-multiple`,
  
  // Categories
  CATEGORIES: '/product-categories',
  CATEGORY_DETAIL: (id) => `/product-categories/${id}`,

  // Variants
  VARIANTS: (productId) => `/products/${productId}/variants`,
  VARIANT_DETAIL: (id) => `/variants/${id}`,
  VARIANT_IMAGES_ATTACH_MULTIPLE: (variantId) => `/variants/${variantId}/images/attach-multiple`,
  
  // Import/Export
  PRODUCTS_IMPORT: '/products/import',
  PRODUCTS_EXPORT: '/products/export',
};

/**
 * Get products list with filters and pagination
 * @param {Object} params - Query parameters
 * @param {string} params.search - Search keyword
 * @param {number} params.category_id - Category ID
 * @param {string} params.product_type - single|combo|service
 * @param {string} params.status - active|inactive|out_of_stock
 * @param {boolean} params.is_active - true|false
 * @param {string} params.brand - Brand name
 * @param {number} params.price_from - Min price
 * @param {number} params.price_to - Max price
 * @param {number} params.stock_from - Min stock
 * @param {number} params.stock_to - Max stock
 * @param {string} params.sort_by - Sort field
 * @param {string} params.order - asc|desc
 * @param {number} params.page - Page number
 * @param {number} params.limit - Items per page
 * @returns {Promise<Object>}
 */
export const getProducts = async (params = {}) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.BASE, { 
      params: {
        ...params,
        // Ensure pagination params
        page: params.page || 1,
        limit: params.limit || 20,
      },
      paramsSerializer: {
        indexes: null, // PHP-style: category_id[]=1&category_id[]=2
      }
    });
    return response.data;
  } catch (error) {
    console.error('getProducts Error:', error);
    throw error;
  }
};

/**
 * Get product detail by ID
 * @param {number} id - Product ID
 * @returns {Promise<Object>}
 */
export const getProductDetail = async (id) => {
  try {
    const productId = parseInt(id, 10);
    // ✅ ADD: Validate ID
    if (isNaN(productId) || productId <= 0) {
      throw new Error(`Invalid product ID: ${id}`);
    }

    const response = await axiosInstance.get(ENDPOINTS.DETAIL(id));
    return response.data;
  } catch (error) {
    // ✅ ADD: Handle 404 gracefully
    if (error.response?.status === 404) {
      console.warn(`Product with ID ${id} not found`);
      return { success: false, data: null, message: 'Product not found' };
    }
    console.error('getProductDetail Error:', error);
    throw error;
  }
};

/**
 * Get product by ID (alias for getProductDetail)
 * @param {number} id - Product ID
 * @returns {Promise<Object>}
 */
export const getProductById = getProductDetail;

/**
 * Get product with variants (composite call if needed)
 * Automatically fetches variants if not included in product response
 * @param {number} productId - Product ID
 * @returns {Promise<Object>} { success, data: { ...product, variants: [...] } }
 */
export const getProductWithVariants = async (productId) => {
  try {
    const productResponse = await getProductDetail(productId);
    if (!productResponse.success) {
      return productResponse; // Return error response
    }
    const product = productResponse.data;
    // ✅ Check if backend already includes variants
    if (product.variants && Array.isArray(product.variants)) {
      console.log('✅ Product already has variants in response');
      return productResponse;
    }
    
    // ✅ If not, fetch variants separately (only if has_variants = true)
    if (product.has_variants) {
      console.log('🔵 Fetching variants separately for product', productId);
      const variantsResponse = await getVariantsByProduct(productId);
      
      if (variantsResponse.success && variantsResponse.data.variants) {
        product.variants = variantsResponse.data.variants;
      }
    }
    
    return {
      success: true,
      data: product,
      message: 'Product with variants loaded successfully'
    };
  } catch (error) {
    console.error('getProductWithVariants Error:', error);
    throw error;
  }
};

/**
 * Create new product
 * @param {Object} data - Product data
 * @param {string} data.code - Product code (required)
 * @param {string} data.name - Product name (required)
 * @param {array} data.category_id - Category ID (required)
 * @param {string} data.product_type - single|combo|service (required)
 * @param {string} data.unit - Unit (required)
 * @param {number} data.selling_price - Selling price (required)
 * @param {number} data.purchase_price - Cost price
 * @param {number} data.wholesale_price - Wholesale price
 * @param {number} data.stock_quantity - Stock quantity
 * @param {number} data.min_stock_threshold - Min stock alert
 * @param {number} data.max_stock_threshold - Max stock alert
 * @param {string} data.barcode - Barcode
 * @param {string} data.brand - Brand name
 * @param {number} data.weight - Weight
 * @param {string} data.description - Description (HTML)
 * @param {string} data.notes - Notes
 * @param {number} data.warranty_period - Warranty period
 * @param {boolean} data.is_active - Active status
 * @param {boolean} data.is_featured - Featured flag
 * @param {boolean} data.is_sellable - Sellable flag
 * @param {Array<string>} data.images - Array of image URLs
 * @returns {Promise<Object>}
 */
/*
export const createProduct = async (data) => {
  try {
    // ✅ ADD: Validate required fields
    const requiredFields = ['code', 'name', 'category_id', 'product_type', 'unit', 'selling_price'];
    const missingFields = requiredFields.filter(field => !data[field]);
    
    if (missingFields.length > 0) {
      throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
    }

    const response = await axiosInstance.post(ENDPOINTS.BASE, data);
    return response.data;
  } catch (error) {
    console.error('createProduct Error:', error);
    throw error;
  }
};*/
export const createProduct = async (data) => {
  try {
    const requiredFields = ['code', 'name', 'category_id', 'product_type', 'unit', 'selling_price'];
    const missingFields = requiredFields.filter(field => {
      const value = data[field];
      if (field === 'category_id') {
        return !value || !Array.isArray(value) || value.length === 0;
      }
      return !value;
    });
    
    if (missingFields.length > 0) {
      throw new Error(`Thiếu trường bắt buộc: ${missingFields.join(', ')}`);
    }

    // ✅ Parse category_id sang array integer
    const submitData = {
      ...data,
      category_id: Array.isArray(data.category_id)
        ? data.category_id.map(id => parseInt(id, 10))
        : [parseInt(data.category_id, 10)]
    };

    const response = await axiosInstance.post(ENDPOINTS.BASE, submitData);
    return response.data;
  } catch (error) {
    console.error('createProduct Error:', error);
    throw error;
  }
};

/**
 * Update existing product
 * @param {number} id - Product ID
 * @param {Object} data - Updated data (same structure as createProduct)
 * @returns {Promise<Object>}
 */
export const updateProduct = async (id, data) => {
  try {
    // ✅ ADD: Validate ID
    const productId = parseInt(id, 10);
    if (isNaN(productId) || productId <= 0) {
      throw new Error(`Invalid product ID: ${id}`);
    }

    // ✅ ADD: Validate data not empty
    if (!data || Object.keys(data).length === 0) {
      throw new Error('No data provided to update');
    }

    const response = await axiosInstance.put(ENDPOINTS.DETAIL(id), data);
    return response.data;
  } catch (error) {
    console.error('updateProduct Error:', error);
    throw error;
  }
};

/**
 * Delete product (soft delete)
 * @param {number} id - Product ID
 * @returns {Promise<Object>}
 */
export const deleteProduct = async (id, confirmDelete = false) => {
  try {
    const productId = parseInt(id, 10);
    if (isNaN(productId) || productId <= 0) {
      throw new Error(`Invalid product ID: ${id}`);
    }

    // ✅ ADD: Optional confirmation
    if (!confirmDelete) {
      console.warn('Deleting product:', id, '- Set confirmDelete=true to proceed');
      throw new Error('Delete action must be confirmed. Set confirmDelete=true');
    }

    const response = await axiosInstance.delete(ENDPOINTS.DETAIL(id));
    return response.data;
  } catch (error) {
    console.error('deleteProduct Error:', error);
    throw error;
  }
};

/**
 * Check if product code exists
 * @param {string} code - Product code
 * @param {number} excludeId - Product ID to exclude (for update)
 * @returns {Promise<Object>} { exists: boolean, message: string }
 */
export const checkProductCode = async (code, excludeId = null) => {
  try {
    const params = { code };
    if (excludeId) params.exclude_id = excludeId;
    
    const response = await axiosInstance.post(ENDPOINTS.CHECK_CODE, params);
    return response.data;
  } catch (error) {
    console.error('checkProductCode Error:', error);
    throw error;
  }
};

/**
 * Get product images
 */
export const getProductImages = async (productId) => {
  try {
    const response = await axiosInstance.get(`/products/${productId}/images`);
    return response.data;
  } catch (error) {
    console.error('Get images error:', error);
    return {
      success: false,
      message: error.response?.data?.message || 'Failed to load images',
      data: []
    };
  }
};

/**
 * Upload product image
 */
export const uploadProductImage = async (file, productId) => {
  try {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('product_id', productId);

    const response = await axiosInstance.post('/products/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data;
  } catch (error) {
    console.error('Upload image error:', error);
    return {
      success: false,
      message: error.response?.data?.message || 'Upload failed',
    };
  }
};

/**
 * Upload multiple product images at once
 * POST /products/upload-multiple
 * 
 * @param {Array<File>} files - Array of image files
 * @param {number} productId - Product ID
 * @returns {Promise<Object>} { success, data: [uploaded images], message, counts }
 */
export const uploadMultipleProductImages = async (files, productId) => {
  try {
    console.log('🔵 uploadMultipleProductImages called:', { 
      fileCount: files.length, 
      productId 
    });

    // Validate inputs
    if (!Array.isArray(files) || files.length === 0) {
      throw new Error('Files array is required and must not be empty');
    }

    const id = parseInt(productId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid product ID: ${productId}`);
    }

    // Validate each file
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB

    files.forEach((file, index) => {
      if (!validTypes.includes(file.type)) {
        throw new Error(`File ${index + 1}: Invalid type (${file.type}). Only JPG, PNG, GIF, WEBP allowed`);
      }
      if (file.size > maxSize) {
        throw new Error(`File ${index + 1}: Too large (${(file.size / 1024 / 1024).toFixed(2)}MB). Max 5MB`);
      }
    });

    // Build FormData
    const formData = new FormData();
    formData.append('product_id', id);
    
    // Append multiple files with same field name 'files[]'
    files.forEach((file) => {
      formData.append('files[]', file);
    });

    console.log('✅ Uploading', files.length, 'files to product', id);

    const response = await axiosInstance.post('/products/upload-multiple', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    console.log('🟢 Upload success:', response.data);

    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || `Uploaded ${files.length} images successfully`,
      uploaded_count: response.data.uploaded_count || files.length,
      failed_count: response.data.failed_count || 0,
      errors: response.data.errors || [],
    };
  } catch (error) {
    console.error('❌ uploadMultipleProductImages Error:', error);
    throw new Error(
      error.message || 
      error.response?.data?.message || 
      'Failed to upload images'
    );
  }
};


/**
 * Set primary image
 */
export const setPrimaryImage = async (imageId) => {
  try {
    const response = await axiosInstance.put(`/products/images/${imageId}/set-primary`);
    return response.data;
  } catch (error) {
    console.error('Set primary image error:', error);
    return {
      success: false,
      message: error.response?.data?.message || 'Failed',
    };
  }
};

/**
 * Delete product image - SOFT or HARD
 * DELETE /api/products/images/:id
 * 
 * @param {number} imageId - Product image ID
 * @param {boolean} hardDelete - true for hard delete (permanent), false/default for soft delete
 * @returns {Promise<Object>}
 */
/**
 * ✅ FIXED: Delete product image with better error handling
 */
export const deleteProductImage = async (imageId, hardDelete = false) => {
  try {
    const params = hardDelete ? { hard: 1 } : {};
    
    console.log('🗑️ deleteProductImage:', { imageId, hardDelete, params });
    
    const response = await axiosInstance.delete(
      `/products/images/${imageId}`,
      { params }
    );
    
    console.log('✅ Delete response:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ deleteProductImage Error:', {
      imageId,
      hardDelete,
      status: error.response?.status,
      message: error.message,
      data: error.response?.data
    });
    
    // ✅ FIX: Rethrow with status for better handling
    throw {
      status: error.response?.status || 500,
      message: error.message || 'Lỗi xóa ảnh',
      response: error.response
    };
  }
};

/**
 * Import products from CSV/Excel
 * @param {File} file - CSV/Excel file
 * @returns {Promise<Object>} { success: boolean, data: { imported: number, failed: number, errors: Array } }
 */
export const importProducts = async (file) => {
  try {
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await axiosInstance.post(
      ENDPOINTS.IMPORT,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  } catch (error) {
    console.error('importProducts Error:', error);
    throw error;
  }
};

/**
 * Export products to CSV/Excel
 * @param {Object} params - Filter params (same as getProducts)
 * @returns {Promise<Blob>}
 */
export const exportProducts = async (params = {}) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.EXPORT, {
      params,
      responseType: 'blob', // Important for file download
    });
    return response.data;
  } catch (error) {
    console.error('exportProducts Error:', error);
    throw error;
  }
};

/**
 * Get product analytics/statistics
 * @param {number} id - Product ID
 * @param {Object} params - Date range params
 * @param {string} params.from_date - Start date (YYYY-MM-DD)
 * @param {string} params.to_date - End date (YYYY-MM-DD)
 * @returns {Promise<Object>}
 */
export const getProductAnalytics = async (id, params = {}) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.ANALYTICS(id), { params });
    return response.data;
  } catch (error) {
    console.error('getProductAnalytics Error:', error);
    throw error;
  }
};

/**
 * Download exported file helper
 * @param {Blob} blob - File blob from exportProducts
 * @param {string} filename - Filename (default: products.xlsx)
 */
export const downloadFile = (blob, filename = 'products.xlsx') => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};

/**
 * Get products with variants aggregated
 * GET /api/products?include_variants=true
 * 
 * @param {Object} params - Same as getProducts + include_variants
 * @returns {Promise<Object>} { success, data: [ { ...product, variants: [...] } ], pagination }
 */
export const getProductsWithVariants = async (params = {}) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.BASE, { 
      params: {
        ...params,
        include_variants: true, // Always include variants
        page: params.page || 1,
        limit: params.limit || 20,
      }
    });
    return response.data;
  } catch (error) {
    console.error('getProductsWithVariants Error:', error);
    throw error;
  }
};

// ========================================
// 🆕 PRODUCT VARIANTS ENDPOINTS - NEWLY ADDED
// ========================================

const VARIANT_ENDPOINTS = {
  BY_PRODUCT: (id) => `/products/${id}/variants`,
  DETAIL: (id) => `/variants/${id}`,
};

/**
 * Get all variants for a product
 * GET /api/products/:id/variants
 * 
 * @param {number} productId - Product ID
 * @returns {Promise<Object>} { success, data: { product, variants, variant_count, total_stock } }
 */
export const getVariantsByProduct = async (productId) => {
  try {
    const response = await axiosInstance.get(VARIANT_ENDPOINTS.BY_PRODUCT(productId));
    return response.data;
  } catch (error) {
    console.error('getVariantsByProduct Error:', error);
    throw error;
  }
};

/**
 * Get variant detail
 * GET /api/variants/{id}
 * 
 * @param {number} variantId - Variant ID
 * @returns {Promise<Object>}
 */
export const getVariant = async (variantId) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }
    
    console.log(`Fetching variant ${id} from /variants/${id}`);
    
    const response = await axiosInstance.get(`/variants/${id}`);
    
    //console.log('getVariant response:', response.data);
    
    return response.data;
  } catch (error) {
    console.error('getVariant Error:', error);
    
    // Return error object so frontend can handle it
    if (error.response?.status === 404) {
      return {
        success: false,
        message: `Variant với ID ${variantId} không tồn tại`,
        data: null
      };
    }
    
    throw error;
  }
};

/**
 * Create new variant for product
 * POST /api/products/:id/variants
 * 
 * @param {number} productId - Product ID
 * @param {Object} data - Variant data
 * @param {string} data.variant_name - Variant name (required)
 * @param {string} data.sku - SKU (required)
 * @param {string} data.barcode - Barcode (optional)
 * @param {number} data.price - Selling price (required)
 * @param {number} data.cost_price - Cost price (optional)
 * @param {number} data.stock_quantity - Stock quantity (optional)
 * @param {string} data.image_url - Image URL (optional)
 * @param {Object} data.attributes - Attributes JSON {color: 'red', size: 'M'} (optional)
 * @param {string} data.status - 'active' | 'inactive' (optional, default: active)
 * @returns {Promise<Object>} { success, message, data: { id, sku, ... } }
 */
export const createVariant = async (productId, data) => {
  try {
    // Use FormData for multipart submission
    const formData = new FormData();
    
    Object.keys(data).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) {
        if (key === 'attributes' && typeof data[key] === 'object') {
          // Convert object to JSON string
          formData.append(key, JSON.stringify(data[key]));
        } else {
          formData.append(key, data[key]);
        }
      }
    });

    const response = await axiosInstance.post(
      VARIANT_ENDPOINTS.BY_PRODUCT(productId),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return response.data;
  } catch (error) {
    console.error('createVariant Error:', error);
    throw error;
  }
};

/**
 * Update variant
 * PUT /api/variants/{id}
 * 
 * @param {number} variantId - Variant ID
 * @param {Object} data - Updated variant data
 * @returns {Promise<Object>}
 */
export const updateVariant = async (variantId, data) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }
    // ✅ CORRECT endpoint from backend - PUT
    const response = await axiosInstance.put(`/variants/${id}`, data);
    return response.data;
  } catch (error) {
    console.error('updateVariant Error:', error);
    throw error;
  }
};

/**
 * Delete variant
 * DELETE /api/variants/{id}
 * 
 * @param {number} variantId - Variant ID
 * @param {boolean} confirmDelete - Confirmation flag (must be true)
 * @returns {Promise<Object>}
 */
export const deleteVariant = async (variantId, confirmDelete = false) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }
    if (!confirmDelete) {
      throw new Error('Delete action must be confirmed');
    }
    // ✅ CORRECT endpoint from backend - DELETE
    const response = await axiosInstance.delete(`/variants/${id}`);
    return response.data;
  } catch (error) {
    console.error('deleteVariant Error:', error);
    throw error;
  }
};

// ========================================
// 🆕 MEDIA LIBRARY ENDPOINTS - NEW SECTION
// ========================================
/**
 * Get media library - browse all images with pagination
 * GET /api/products/media/library?limit=12&offset=0
 *
 * @param {number} limit - Items per page (default: 50)
 * @param {number} offset - Offset for pagination (default: 0)
 * @param {Object} filters - Additional filters (brand, search, etc.)
 * @returns {Promise<Object>} { success, images, total, limit, offset, message }
 */
/**
 * Get media library with pagination & filters
 * GET /api/products/media/library
 */
export const getMediaLibrary = async (
  limit = 12,
  offset = 0,
  filters = {}
) => {
  try {
    // ✅ Validate inputs
    limit = Math.max(1, Math.min(100, parseInt(limit) || 12));
    offset = Math.max(0, parseInt(offset) || 0);
    
    console.log('🔵 getMediaLibrary called:', { limit, offset, filters });

    const params = {
      limit,
      offset,
      ...filters, // ✅ Spread filters (product_id, brand, sku, year, month)
    };

    const response = await axiosInstance.get(ENDPOINTS.MEDIA_LIBRARY, {
      params,
    });

    console.log('🟢 getMediaLibrary response:', response.data);

    // ✅ FIX: Safe access to pagination
    const pagination = response.data.pagination || {};
    const total = parseInt(pagination.total) || 0;
    const pages = parseInt(pagination.pages) || 0;

    // ✅ FIX: Empty result check
    if (!response.data.data || response.data.data.length === 0) {
      console.info('ℹ️ No images found');
      return {
        success: true,
        images: [],
        total: total,
        limit: limit,
        offset: offset,
        pages: pages,
        message: response.data.message || 'Không có ảnh nào được tìm thấy',
      };
    }

    return {
      success: response.data.success !== false,
      data: response.data.data,
      total: total,  // ✅ Ensure number, not null
      limit: pagination.limit || limit,
      offset: pagination.offset || offset,
      pages: pages,
      message: response.data.message || 'Thư viện ảnh tải thành công',
    };
  } catch (error) {
    console.error('❌ getMediaLibrary Error:', {
      message: error.message,
      status: error.response?.status,
      url: error.config?.url,
      params: error.config?.params,
      data: error.response?.data,
    });

    throw new Error(
      error.message ||
        error.response?.data?.message ||
        'Lỗi tải thư viện ảnh'
    );
  }
};

/**
 * Get media by date range
 * ✅ FIXED: Proper GET request with query params
 * GET /api/products/media/by-date?year=2025&month=11&limit=12&offset=0
 *
 * @param {number} year - Year (e.g., 2025)
 * @param {number} month - Month (1-12)
 * @param {number} limit - Items per page (default: 12)
 * @param {number} offset - Offset for pagination (default: 0)
 * @returns {Promise<Object>} { success, data, total, pagination, message }
 */
export const getMediaByDate = async (year, month, limit = 12, offset = 0) => {
  try {
    console.log('🔵 getMediaByDate called:', { year, month, limit, offset });

    // ✅ Validate inputs
    const validatedYear = parseInt(year, 10);
    const validatedMonth = parseInt(month, 10);
    const validatedLimit = Math.max(1, Math.min(100, parseInt(limit) || 12));
    const validatedOffset = Math.max(0, parseInt(offset) || 0);

    if (isNaN(validatedYear) || validatedYear < 2000) {
      throw new Error(`Invalid year: ${year} (must be >= 2000)`);
    }

    if (isNaN(validatedMonth) || validatedMonth < 1 || validatedMonth > 12) {
      throw new Error(`Invalid month: ${month} (must be 1-12)`);
    }

    // ✅ FIX: Use GET with query params (not POST)
    const response = await axiosInstance.get(ENDPOINTS.MEDIA_BY_DATE, {
      params: {
        year: validatedYear,
        month: validatedMonth,
        limit: validatedLimit,
        offset: validatedOffset,
      },
    });

    console.log('🟢 getMediaByDate response:', response.data);

    // ✅ Handle empty results
    if (!response.data.data || response.data.data.length === 0) {
      console.info(`ℹ️ No images found for ${validatedMonth}/${validatedYear}`);
      return {
        success: true,
        data: [],
        total: 0,
        pagination: {
          total: 0,
          limit: validatedLimit,
          offset: validatedOffset,
          pages: 0
        },
        message: response.data.message || `Không có ảnh trong tháng ${validatedMonth}/${validatedYear}`,
      };
    }

    // ✅ Return standardized format
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      total: response.data.pagination?.total || 0,
      pagination: {
        total: response.data.pagination?.total || 0,
        limit: response.data.pagination?.limit || validatedLimit,
        offset: response.data.pagination?.offset || validatedOffset,
        pages: response.data.pagination?.pages || 0
      },
      message: response.data.message || `Đã tải ${response.data.data.length} ảnh từ ${validatedMonth}/${validatedYear}`,
    };
  } catch (error) {
    console.error('❌ getMediaByDate Error:', {
      message: error.message,
      status: error.response?.status,
      url: error.config?.url,
      params: { year, month, limit, offset },
      responseData: error.response?.data,
    });

    throw {
      status: error.response?.status || 500,
      message: error.response?.data?.message || error.message || 'Lỗi tải ảnh theo ngày',
      url: error.config?.url,
      data: error.response?.data
    };
  }
};

/**
 * Search media by SKU
 * ✅ FIXED: Proper GET request
 * GET /api/products/media/search-sku?sku=SP001&limit=20
 *
 * @param {string} sku - Product SKU/Code to search
 * @param {number} limit - Items per page (default: 20)
 * @returns {Promise<Object>} { success, data, total, message }
 */
export const searchMediaBySku = async (sku, limit = 20) => {
  try {
    console.log('🔵 searchMediaBySku called:', { sku, limit });

    // ✅ Validate SKU input
    if (!sku || typeof sku !== 'string') {
      throw new Error('SKU is required and must be a string');
    }

    const trimmedSku = sku.trim();
    if (trimmedSku.length < 2) {
      console.warn('⚠️ SKU too short');
      return {
        success: true,
        data: [],
        total: 0,
        message: 'SKU phải có ít nhất 2 ký tự',
      };
    }

    const validatedLimit = Math.max(1, Math.min(100, parseInt(limit) || 20));

    // ✅ FIX: Use GET (backend expects GET)
    const response = await axiosInstance.get(ENDPOINTS.MEDIA_SEARCH_SKU, {
      params: {
        sku: trimmedSku,
        limit: validatedLimit,
      },
    });

    console.log('🟢 searchMediaBySku success:', response.data);

    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      total: response.data.total || 0,
      message: response.data.message || `Tìm thấy ${response.data.data?.length || 0} ảnh`,
    };
  } catch (error) {
    console.error('❌ searchMediaBySku Error:', {
      message: error.message,
      status: error.response?.status,
      url: error.config?.url,
      sku,
      data: error.response?.data,
    });

    throw {
      status: error.response?.status || 500,
      message: error.response?.data?.message || error.message || 'Lỗi tìm kiếm ảnh',
      url: error.config?.url
    };
  }
};

/**
 * Attach multiple images to product (batch operation)
 * POST /api/products/{id}/images/attach-multiple
 * Body: { image_ids: [1, 2, 3] }
 *
 * @param {number} productId - Product ID
 * @param {Array<number>} imageIds - Array of image IDs to attach
 * @returns {Promise<Object>} { success, message, attached_count, product_id, data }
 */
export const attachMultipleImages = async (productId, imageIds = []) => {
  try {
    console.log('🔵 attachMultipleImages called:', { productId, imageIds });

    // ✅ FIX: Validate inputs
    const id = parseInt(productId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Product ID không hợp lệ: ${productId}`);
    }

    if (!Array.isArray(imageIds) || imageIds.length === 0) {
      throw new Error('Image IDs phải là array không rỗng');
    }

    // ✅ FIX: Validate all IDs are numbers
    const validatedIds = imageIds.map((imgId) => {
      const parsedId = parseInt(imgId, 10);
      if (isNaN(parsedId) || parsedId <= 0) {
        throw new Error(`Image ID không hợp lệ: ${imgId}`);
      }
      return parsedId;
    });

    console.log('✅ Validation passed, attaching images:', validatedIds);

    const response = await axiosInstance.post(ENDPOINTS.IMAGES_ATTACH_MULTIPLE(id), {
      image_ids: validatedIds,
    });

    console.log('🟢 attachMultipleImages success:', response.data);

    return {
      success: response.data.success !== false,
      message: response.data.message || `Đã gắn ${validatedIds.length} ảnh thành công`,
      attached_count: response.data.attached_count || validatedIds.length,
      product_id: id,
      data: response.data.data || [],
    };
  } catch (error) {
    console.error('❌ attachMultipleImages Error:', {
      message: error.message,
      status: error.response?.status,
      url: error.config?.url,
      productId,
      imageIds,
      data: error.response?.data,
    });

    throw new Error(
      error.message || error.response?.data?.message || 'Lỗi gắn ảnh vào sản phẩm'
    );
  }
};

export const uploadMultipleVariantImages = async (files, variantId) => {
  try {
    if (!Array.isArray(files) || files.length === 0) {
      throw new Error('Files array không hợp lệ hoặc rỗng');
    }

    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`variantId không hợp lệ: ${variantId}`);
    }

    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxSize = 5 * 1024 * 1024;

    files.forEach((file, i) => {
      if (!validTypes.includes(file.type)) {
        throw new Error(`File ${i + 1}: Loại file không hợp lệ (${file.type})`);
      }
      if (file.size > maxSize) {
        throw new Error(`File ${i + 1}: Kích thước quá lớn (max: 5MB)`);
      }
    });

    const formData = new FormData();
    formData.append('variant_id', id);

    files.forEach((file) => {
      formData.append('files[]', file);
    });

    const response = await axiosInstance.post(`/variants/${id}/upload-multiple`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  } catch (error) {
    console.error('uploadMultipleVariantImages Error:', error);
    throw error;
  }
};
export const attachMultipleImagesToVariant = async (variantId, imageIds = []) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`variantId không hợp lệ: ${variantId}`);
    }

    if (!Array.isArray(imageIds) || imageIds.length === 0) {
      throw new Error('Image IDs phải là array không rỗng');
    }

    const validatedIds = imageIds.map((imgId) => {
      const parsedId = parseInt(imgId, 10);
      if (isNaN(parsedId) || parsedId <= 0) {
        throw new Error(`Image ID không hợp lệ: ${imgId}`);
      }
      return parsedId;
    });

    // Gửi lên server
    const response = await axiosInstance.post(`/variants/${id}/images/attach-multiple`, {
      image_ids: validatedIds,
    });

    // Chuẩn hóa response giống bên product
    if (!response.data || response.data.success === false) {
      throw new Error(response.data?.message || 'Gắn ảnh thất bại');
    }

    return {
      success: response.data.success !== false,
      message: response.data.message || `Đã gắn ${validatedIds.length} ảnh thành công`,
      attached_count: response.data.attached_count ?? 0,
      restored_count: response.data.restored_count ?? 0,
      duplicate_count: response.data.duplicate_count ?? 0,
      total_success: response.data.total_success ?? 0,
      variant_id: id,
      data: response.data.data || [],
    };
  } catch (error) {
    // Chuẩn hóa trả về lỗi cho FE dùng, bắt status 500 từ BE tiện log/tùy biến UI
    const resp = error.response?.data;
    throw {
      status: error.response?.status || 500,
      message: resp?.message || error.message || 'Lỗi gắn ảnh vào biến thể',
      data: resp || null,
    };
  }
};

/**
 * Lấy danh sách variant đã xóa mềm
 * GET /api/variants/deleted?product_id=123
 */
export const getDeletedVariants = async (productId) => {
  try {
    const response = await axiosInstance.get('/variants/deleted', {
      params: { product_id: productId }
    });
    return response.data;
  } catch (error) {
    console.error('getDeletedVariants Error:', error);
    throw error;
  }
};

/**
 * Khôi phục variant đã xóa mềm
 * PUT /api/variants/:id/restore
 */
export const restoreVariant = async (variantId) => {
  try {
    const response = await axiosInstance.put(`/variants/${variantId}/restore`);
    return response.data;
  } catch (error) {
    console.error('restoreVariant Error:', error);
    throw error;
  }
};

/**
 * Hard delete variant (xóa vĩnh viễn)
 * @param {number} variantId 
 * @returns {Promise}
 */
export const hardDeleteVariant = async (variantId) => {
  try {
    const response = await axiosInstance.delete(`/variants/${variantId}/hard`);
    // Nếu backend trả về success=true, trả thẳng response
    if (response.data?.success) {
      return response.data;
    }
    // Nếu backend trả về success=false, hoặc không có response.data
    throw new Error(response.data?.message || 'Xóa vĩnh viễn biến thể thất bại');
  } catch (error) {
    // Bổ sung: Nếu backend trả về 400/404 (không tìm thấy), báo message đúng cho FE
    if (error.response && error.response.data) {
      // Có thể là { success: false, message: "...", ... }
      throw new Error(error.response.data.message || 'Xóa vĩnh viễn biến thể thất bại');
    }
    // Lỗi phía FE/network
    throw new Error(error.message || 'Lỗi không xác định khi xóa vĩnh viễn');
  }
};

/**
 * Lấy chi tiết sản phẩm kèm biến thể và attribute values của từng variant
 * @param {number|string} productId
 * @returns {Promise<{success:boolean, data:object, message?:string}>}
 */
export const fetchProductDetailWithVariants = async (productId) => {
  try {
    const response = await axiosInstance.get(`/products/${productId}/detail-with-variants`);
    return response.data;  // { success: true, data: { ...product, variants_v2: [...] } }
  } catch (error) {
    return {
      success: false,
      message: error.response?.data?.message || error.message || 'Lỗi khi lấy chi tiết sản phẩm',
      data: null,
    };
  }
};


// ========================================
// Export all API functions
// ========================================

const productApi = {
  // Products
  getProducts,
  getProductDetail,
  getProductById,              // ✅ NEW
  getProductWithVariants,      // ✅ NEW (thêm vào đây)
  createProduct,
  updateProduct,
  deleteProduct,
  checkProductCode,
  uploadProductImage,
  uploadMultipleProductImages,
  importProducts,
  exportProducts,
  getProductAnalytics,
  downloadFile,
  getProductsWithVariants,
  // ✅ NEW: Variants
  getVariantsByProduct,
  getVariant,
  createVariant,
  updateVariant,
  deleteVariant,
  uploadMultipleVariantImages,
  attachMultipleImagesToVariant,
  getDeletedVariants,
  restoreVariant,
  hardDeleteVariant,
  fetchProductDetailWithVariants,
  // ✅ NEW: Media Library
  getMediaLibrary,
  getMediaByDate,
  searchMediaBySku,
  attachMultipleImages,
};

export default productApi;