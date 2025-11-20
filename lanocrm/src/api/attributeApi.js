/**
 * Attribute API Service
 * @file src/api/attributeApi.js
 * @description API calls for Product Attributes module
 * @backend https://banhang.tuidanam.org/backend-ci/api
 */

import axiosInstance from './axios';

/**
 * API Endpoints
 * ✅ FIX: Thêm tiền tố '/api' cho tất cả endpoints
 */
const ENDPOINTS = {
  // Attributes
  BASE: '/attributes',  // ✅ BỎ /api
  DETAIL: (id) => `/attributes/${id}`,  // ✅ BỎ /api
  
  // Attribute Options
  OPTIONS: (id) => `/attributes/${id}/options`,  // ✅ BỎ /api
  OPTION_DETAIL: (id) => `/attributes/options/${id}`,  // ✅ BỎ /api
  
  // Product Attribute Values
  PRODUCT_VALUES: (productId) => `/products/${productId}/attribute-values`,  // ✅ BỎ /api
  
  // Variant Attribute Values
  VARIANT_VALUES: (variantId) => `/variants/${variantId}/attribute-values`,  // ✅ BỎ /api
  VARIANT_VALUES_SYNC: (variantId) => `/variants/${variantId}/attribute-values/sync`,  // ✅ BỎ /api
  
  // Single Attribute Value Operations
  CREATE_VALUE: '/attribute-values',  // ✅ BỎ /api
  DELETE_VALUE: (id) => `/attribute-values/${id}`,  // ✅ BỎ /api
};

// ========================================
// ATTRIBUTES CRUD
// ========================================

/**
 * Get all attributes
 * GET /api/attributes
 * 
 * @param {Object} params - Query parameters
 * @param {string} params.search - Search keyword
 * @param {string} params.type - Filter by type (text|select|color|image)
 * @param {string} params.status - Filter by status (active|inactive)
 * @returns {Promise<Object>} { success, data: [...], message }
 */
export const getAttributes = async (params = {}) => {
  try {
    
    const response = await axiosInstance.get(ENDPOINTS.BASE, { params });
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải danh sách thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ getAttributes Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tải danh sách thuộc tính'
    );
  }
};

/**
 * Get attribute detail by ID
 * GET /api/attributes/:id
 * 
 * @param {number} id - Attribute ID
 * @returns {Promise<Object>} { success, data: {...}, message }
 */
export const getAttributeDetail = async (id) => {
  try {
    const attributeId = parseInt(id, 10);
    if (isNaN(attributeId) || attributeId <= 0) {
      throw new Error(`Invalid attribute ID: ${id}`);
    }
    
    console.log('🔵 getAttributeDetail called:', id);
    
    const response = await axiosInstance.get(ENDPOINTS.DETAIL(id));
    
    console.log('🟢 getAttributeDetail response:', response.data);
    
    if (!response.data.data) {
      throw new Error('Không tìm thấy thuộc tính');
    }
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Tải thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ getAttributeDetail Error:', error);
    
    if (error.response?.status === 404) {
      return {
        success: false,
        data: null,
        message: `Thuộc tính với ID ${id} không tồn tại`,
      };
    }
    
    throw error;
  }
};

/**
 * Create new attribute
 * POST /api/attributes
 * 
 * @param {Object} data - Attribute data
 * @param {string} data.name - Attribute name (required)
 * @param {string} data.slug - Slug (optional, auto-generated)
 * @param {string} data.type - Type: text|select|color|image (default: select)
 * @param {boolean} data.is_required - Required flag (default: false)
 * @param {boolean} data.is_filterable - Filterable flag (default: true)
 * @param {number} data.sort_order - Sort order (default: 0)
 * @param {string} data.status - Status: active|inactive (default: active)
 * @returns {Promise<Object>} { success, data: { id, ... }, message }
 */
export const createAttribute = async (data) => {
  try {
    console.log('🔵 createAttribute called:', data);
    
    // Validate required fields
    if (!data.name || !data.name.trim()) {
      throw new Error('Tên thuộc tính là bắt buộc');
    }
    
    const response = await axiosInstance.post(ENDPOINTS.BASE, data);
    
    console.log('🟢 createAttribute response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Tạo thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ createAttribute Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tạo thuộc tính'
    );
  }
};

/**
 * Update attribute
 * PUT /api/attributes/:id
 * 
 * @param {number} id - Attribute ID
 * @param {Object} data - Updated attribute data
 * @returns {Promise<Object>} { success, data, message }
 */
export const updateAttribute = async (id, data) => {
  try {
    const attributeId = parseInt(id, 10);
    if (isNaN(attributeId) || attributeId <= 0) {
      throw new Error(`Invalid attribute ID: ${id}`);
    }
    
    console.log('🔵 updateAttribute called:', { id, data });
    
    const response = await axiosInstance.put(ENDPOINTS.DETAIL(id), data);
    
    console.log('🟢 updateAttribute response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Cập nhật thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ updateAttribute Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi cập nhật thuộc tính'
    );
  }
};

/**
 * Delete attribute (soft delete)
 * DELETE /api/attributes/:id
 * 
 * @param {number} id - Attribute ID
 * @returns {Promise<Object>} { success, message }
 */
export const deleteAttribute = async (id) => {
  try {
    const attributeId = parseInt(id, 10);
    if (isNaN(attributeId) || attributeId <= 0) {
      throw new Error(`Invalid attribute ID: ${id}`);
    }
    
    console.log('🔵 deleteAttribute called:', id);
    
    const response = await axiosInstance.delete(ENDPOINTS.DETAIL(id));
    
    console.log('🟢 deleteAttribute response:', response.data);
    
    return {
      success: response.data.success !== false,
      message: response.data.message || 'Xóa thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ deleteAttribute Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi xóa thuộc tính'
    );
  }
};

// ========================================
// ATTRIBUTE OPTIONS
// ========================================

/**
 * Get options for an attribute
 * GET /api/attributes/:id/options
 * 
 * @param {number} attributeId - Attribute ID
 * @returns {Promise<Object>} { success, data: [...], message }
 */
export const getAttributeOptions = async (attributeId) => {
  try {
    const id = parseInt(attributeId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid attribute ID: ${attributeId}`);
    }
    
    const response = await axiosInstance.get(ENDPOINTS.OPTIONS(attributeId));
    
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải danh sách giá trị thành công',
    };
  } catch (error) {
    console.error('❌ getAttributeOptions Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tải giá trị thuộc tính'
    );
  }
};

/**
 * Create attribute option
 * POST /api/attributes/:id/options
 * 
 * @param {number} attributeId - Attribute ID
 * @param {Object} data - Option data
 * @param {string} data.option_name - Option name (required) ← ✅ ĐỔI
 * @param {string} data.color_code - Color hex code (optional, for type=color)
 * @param {string} data.image_url - Image URL (optional, for type=image)
 * @param {number} data.sort_order - Sort order (default: 0)
 * @returns {Promise<Object>} { success, data, message }
 */
export const createAttributeOption = async (attributeId, data) => {
  try {
    const id = parseInt(attributeId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid attribute ID: ${attributeId}`);
    }
    
    if (!data.option_name || !data.option_name.trim()) {
      throw new Error('Giá trị thuộc tính là bắt buộc');
    }
    
    console.log('🔵 createAttributeOption called:', { attributeId, data });
    
    const response = await axiosInstance.post(ENDPOINTS.OPTIONS(attributeId), data);
    
    console.log('🟢 createAttributeOption response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Tạo giá trị thành công',
    };
  } catch (error) {
    console.error('❌ createAttributeOption Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tạo giá trị thuộc tính'
    );
  }
};

/**
 * Update attribute option
 * PUT /api/attribute-options/:id
 * 
 * @param {number} optionId - Option ID
 * @param {Object} data - Updated option data
 * @returns {Promise<Object>} { success, data, message }
 */
export const updateAttributeOption = async (optionId, data) => {
  try {
    const id = parseInt(optionId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid option ID: ${optionId}`);
    }
    
    console.log('🔵 updateAttributeOption called:', { optionId, data });
    
    const response = await axiosInstance.put(ENDPOINTS.OPTION_DETAIL(optionId), data);
    
    console.log('🟢 updateAttributeOption response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Cập nhật giá trị thành công',
    };
  } catch (error) {
    console.error('❌ updateAttributeOption Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi cập nhật giá trị thuộc tính'
    );
  }
};

/**
 * Delete attribute option
 * DELETE /api/attribute-options/:id
 * 
 * @param {number} optionId - Option ID
 * @returns {Promise<Object>} { success, message }
 */
export const deleteAttributeOption = async (optionId) => {
  try {
    const id = parseInt(optionId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid option ID: ${optionId}`);
    }
    
    console.log('🔵 deleteAttributeOption called:', optionId);
    
    const response = await axiosInstance.delete(ENDPOINTS.OPTION_DETAIL(optionId));
    
    console.log('🟢 deleteAttributeOption response:', response.data);
    
    return {
      success: response.data.success !== false,
      message: response.data.message || 'Xóa giá trị thành công',
    };
  } catch (error) {
    console.error('❌ deleteAttributeOption Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi xóa giá trị thuộc tính'
    );
  }
};

// ========================================
// PRODUCT ATTRIBUTE VALUES
// ========================================

/**
 * Get attribute values for a product (simple product)
 * GET /api/products/:id/attribute-values
 * 
 * @param {number} productId - Product ID
 * @returns {Promise<Object>} { success, data: [...], message }
 */
export const getProductAttributeValues = async (productId) => {
  try {
    const id = parseInt(productId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid product ID: ${productId}`);
    }
    
    console.log('🔵 getProductAttributeValues called:', productId);
    
    const response = await axiosInstance.get(ENDPOINTS.PRODUCT_VALUES(productId));
    
    console.log('🟢 getProductAttributeValues response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải thuộc tính sản phẩm thành công',
    };
  } catch (error) {
    console.error('❌ getProductAttributeValues Error:', error);
    
    // Return empty array instead of throwing for 404
    if (error.response?.status === 404) {
      return {
        success: true,
        data: [],
        message: 'Sản phẩm chưa có thuộc tính',
      };
    }
    
    throw error;
  }
};

/**
 * Update product attribute values (batch)
 * POST /api/products/:id/attribute-values (override all)
 * 
 * @param {number} productId - Product ID
 * @param {Array} attributeValues - Array of { attribute_id, option_id? }
 * @returns {Promise<Object>} { success, data, message }
 */
// attributeApi.js
export const updateProductAttributeValues = async (productId, payload) => {
  try {
    const id = parseInt(productId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid product ID: ${productId}`);
    }
    
    // Kiểm tra payload truyền vào có key attribute_values
    if (!payload || !Array.isArray(payload.attribute_values)) {
      throw new Error('Payload phải là object có key attribute_values là array');
    }
    console.log('🔵 updateProductAttributeValues called:', { productId, attribute_values: payload.attribute_values });
    const response = await axiosInstance.post(
      ENDPOINTS.PRODUCT_VALUES(productId),
      payload
    );
    console.log('🟢 updateProductAttributeValues response:', response.data);
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Cập nhật thuộc tính sản phẩm thành công',
    };
  } catch (error) {
    console.error('❌ updateProductAttributeValues Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi cập nhật thuộc tính sản phẩm'
    );
  }
};

// ========================================
// VARIANT ATTRIBUTE VALUES
// ========================================

/**
 * Get attribute values for a variant
 * GET /api/variants/:id/attribute-values
 * 
 * @param {number} variantId - Variant ID
 * @returns {Promise<Object>} { success, data: [...], message }
 */
export const getVariantAttributeValues = async (variantId) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }
    
    console.log('🔵 getVariantAttributeValues called:', variantId);
    
    const response = await axiosInstance.get(ENDPOINTS.VARIANT_VALUES(variantId));
    
    console.log('🟢 getVariantAttributeValues response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải thuộc tính biến thể thành công',
    };
  } catch (error) {
    console.error('❌ getVariantAttributeValues Error:', error);
    
    // Return empty array for 404
    if (error.response?.status === 404) {
      return {
        success: true,
        data: [],
        message: 'Biến thể chưa có thuộc tính',
      };
    }
    
    throw error;
  }
};

/**
 * Get variant detail by ID
 * GET /api/variants/:id
 * 
 * @param {number} variantId
 * @returns {Promise<Object>} { success, data, message }
 */
export const getVariantById = async (variantId) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }

    const response = await axiosInstance.get(`/variants/${id}`);
    return {
      success: response.data.success !== false,
      data: response.data.data || null,
      message: response.data.message || ''
    };
  } catch (error) {
    console.error('getVariantById Error:', error);
    throw error;
  }
};


/**
 * Sync variant attribute values (batch update)
 * POST /api/variants/:id/attribute-values/sync
 * 
 * @param {number} variantId - Variant ID
 * @param {Array} attributeValues - Array of { attribute_id, option_id? }
 * @returns {Promise<Object>} { success, data, message }
 */
export const syncVariantAttributeValues = async (variantId, attributeValues = []) => {
  try {
    const id = parseInt(variantId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid variant ID: ${variantId}`);
    }
    
    if (!Array.isArray(attributeValues)) {
      throw new Error('Attribute values phải là array');
    }
    
    console.log('🔵 syncVariantAttributeValues called:', { variantId, attributeValues });
    
    const response = await axiosInstance.post(
      ENDPOINTS.VARIANT_VALUES_SYNC(variantId),
      { attribute_values: attributeValues }
    );
    
    console.log('🟢 syncVariantAttributeValues response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Đồng bộ thuộc tính biến thể thành công',
    };
  } catch (error) {
    console.error('❌ syncVariantAttributeValues Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi đồng bộ thuộc tính biến thể'
    );
  }
};

export async function removeAttributeFromVariant(variantId, attributeId) {
  return axiosInstance.delete(`/attributes/remove-from-variant/${variantId}/${attributeId}`);
}

export const removeAttributeFromProduct = (productId, attributeId) =>
  axiosInstance.delete(`/products/${productId}/attribute-values/${attributeId}`);

// ========================================
// SINGLE VALUE OPERATIONS (Optional)
// ========================================

/**
 * Create single attribute value
 * POST /api/attribute-values/create
 * 
 * @param {Object} data
 * @param {number} data.product_id - Product ID (for simple product)
 * @param {number} data.variant_id - Variant ID (for variant)
 * @param {number} data.attribute_id - Attribute ID
 * @param {string} data.option_name - Value text (for type=text)
 * @param {number} data.option_id - Option ID (for type=select/color/image)
 * @returns {Promise<Object>} { success, data, message }
 */
export const createAttributeValue = async (data) => {
  try {
    console.log('🔵 createAttributeValue called:', data);
    
    // Validate
    if (!data.attribute_id) {
      throw new Error('Attribute ID là bắt buộc');
    }
    
    if (!data.product_id && !data.variant_id) {
      throw new Error('Phải có product_id hoặc variant_id');
    }
    
    const response = await axiosInstance.post(ENDPOINTS.CREATE_VALUE, data);
    
    console.log('🟢 createAttributeValue response:', response.data);
    
    return {
      success: response.data.success !== false,
      data: response.data.data,
      message: response.data.message || 'Tạo giá trị thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ createAttributeValue Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tạo giá trị thuộc tính'
    );
  }
};

/**
 * Delete single attribute value
 * DELETE /api/attribute-values/:id
 * 
 * @param {number} valueId - Attribute value ID
 * @returns {Promise<Object>} { success, message }
 */
export const deleteAttributeValue = async (valueId) => {
  try {
    const id = parseInt(valueId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid value ID: ${valueId}`);
    }
    
    console.log('🔵 deleteAttributeValue called:', valueId);
    
    const response = await axiosInstance.delete(ENDPOINTS.DELETE_VALUE(valueId));
    
    console.log('🟢 deleteAttributeValue response:', response.data);
    
    return {
      success: response.data.success !== false,
      message: response.data.message || 'Xóa giá trị thuộc tính thành công',
    };
  } catch (error) {
    console.error('❌ deleteAttributeValue Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi xóa giá trị thuộc tính'
    );
  }
};
/**
 * Tạo biến thể từ attribute của sản phẩm
 * POST /api/attributes/:id/generate-variants
 * 
 * @param {number} productId - Product ID
 * @returns {Promise<Object>} { success, message }
 */
export const generateVariantsFromAttributes = async (productId) => {
  try {
    const id = parseInt(productId, 10);
    if (isNaN(id) || id <= 0) {
      throw new Error(`Invalid product ID: ${productId}`);
    }

    console.log('🔵 generateVariantsFromAttributes called:', productId);

    const response = await axiosInstance.post(`/attributes/${id}/generate-variants`);

    console.log('🟢 generateVariantsFromAttributes response:', response.data);

    return {
      success: response.data.success !== false,
      message: response.data.message || 'Tạo biến thể thành công',
      data: response.data.data || {}, // ✅ THÊM: Trả về data
    };
  } catch (error) {
    console.error('❌ generateVariantsFromAttributes Error:', error);
    throw new Error(
      error.response?.data?.message ||
      error.message ||
      'Lỗi tạo biến thể'
    );
  }
};

/**
 * Get products/variants using this attribute
 * GET /api/attributes/:id/products
 * @param {number} attributeId
 * @param {boolean} includeDeleted - Có lấy cả variant bị xóa mềm không
 */
export const getProductsByAttribute = async (attributeId, includeDeleted = false) => {
  try {
    const params = includeDeleted ? { include_deleted: '1' } : {};
    const response = await axiosInstance.get(`/attributes/${attributeId}/products`, { params });
    
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải danh sách thành công',
    };
  } catch (error) {
    console.error('❌ getProductsByAttribute Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tải danh sách sản phẩm'
    );
  }
};

export const getUsedOptionsByProduct = async (productId) => {
  try {
    const response = await axiosInstance.get(`/products/${productId}/used-attribute-options`);
    return response.data;
  } catch (error) {
    console.error('getUsedOptionsByProduct Error:', error);
    throw error;
  }
};

/**
 * Get products/variants using this option
 * GET /api/attributes/options/:id/products
 */
export const getProductsByOption = async (optionId, includeDeleted = false) => {
  try {
    const params = includeDeleted ? { include_deleted: '1' } : {};
    const response = await axiosInstance.get(`/attributes/options/${optionId}/products`, { params });
    
    return {
      success: response.data.success !== false,
      data: response.data.data || [],
      message: response.data.message || 'Tải danh sách thành công',
    };
  } catch (error) {
    console.error('❌ getProductsByOption Error:', error);
    throw new Error(
      error.response?.data?.message || 
      error.message || 
      'Lỗi tải danh sách sản phẩm'
    );
  }
};


// ========================================
// Export all functions
// ========================================

const attributeApi = {
  // Attributes CRUD
  getAttributes,
  getAttributeDetail,
  createAttribute,
  updateAttribute,
  deleteAttribute,
  
  // Attribute Options
  getAttributeOptions,
  createAttributeOption,
  updateAttributeOption,
  deleteAttributeOption,
  getProductsByOption,
  
  // Product Attribute Values
  getProductAttributeValues,
  updateProductAttributeValues,
  generateVariantsFromAttributes,
  getProductsByAttribute,
  getUsedOptionsByProduct,
  getVariantById,

  // Variant Attribute Values
  getVariantAttributeValues,
  syncVariantAttributeValues,
  removeAttributeFromVariant,
  
  // Single Value Operations
  createAttributeValue,
  deleteAttributeValue,
};

export default attributeApi;