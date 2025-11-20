/**
 * Category API Service
 * @file src/api/categoryApi.js
 * @description API calls for Product Categories
 * @backend https://banhang.tuidanam.org/backend-ci/api
 */

import axiosInstance from './axios';

/**
 * API Endpoints
 */
const ENDPOINTS = {
  BASE: '/product-categories',
  TREE: '/product-categories',
  DETAIL: (id) => `/product-categories/${id}`,
};

/**
 * Get categories list
 * @param {Object} params - Query parameters
 * @param {string} params.search - Search keyword
 * @param {number} params.parent_id - Parent category ID
 * @param {boolean} params.is_active - Active status filter
 * @param {string} params.sort_by - Sort field
 * @param {string} params.order - asc|desc
 * @param {number} params.page - Page number
 * @param {number} params.limit - Items per page
 * @returns {Promise<Object>}
 */
export const getCategories = async (params = {}) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.BASE, { params });
    return response.data;
  } catch (error) {
    console.error('getCategories Error:', error);
    throw error;
  }
};

/**
 * Get category tree structure (hierarchical)
 * @returns {Promise<Object>} { success: boolean, data: Array<Category> }
 */
export const getCategoryTree = async (includeDeleted = false) => {
  const params = includeDeleted
    ? { view: 'tree', include_deleted: true }
    : { view: 'tree' };
  try {
    const response = await axiosInstance.get(ENDPOINTS.TREE, { params });
    return response.data;
  } catch (error) {
    console.error('getCategoryTree Error:', error);
    throw error;
  }
};

/**
 * Get products in category (including children)
 * @param {number} category_id - Category ID
 * @returns {Promise<Object>}
 */
export const getCategoryProducts = async (category_id) => {
  try {
    const response = await axiosInstance.get(`/product-categories/${category_id}/products-with-variants`);
    return response.data;
  } catch (error) {
    console.error('getCategoryProducts Error:', error);
    throw error;
  }
};


/**
 * Get category detail by ID
 * @param {number} id - Category ID
 * @returns {Promise<Object>}
 */
export const getCategoryDetail = async (id) => {
  try {
    const response = await axiosInstance.get(ENDPOINTS.DETAIL(id));
    return response.data;
  } catch (error) {
    console.error('getCategoryDetail Error:', error);
    throw error;
  }
};

/**
 * Create new category
 * @param {Object} data - Category data
 * @param {string} data.name - Category name (required)
 * @param {string} data.code - Category code
 * @param {number} data.parent_id - Parent category ID (null for root)
 * @param {string} data.description - Description
 * @param {number} data.sort_order - Sort order
 * @param {boolean} data.is_active - Active status (default: true)
 * @returns {Promise<Object>}
 */
export const createCategory = async (data) => {
  try {
    const response = await axiosInstance.post(ENDPOINTS.BASE, data);
    return response.data;
  } catch (error) {
    console.error('createCategory Error:', error);
    throw error;
  }
};

/**
 * Update existing category
 * @param {number} id - Category ID
 * @param {Object} data - Updated data (same structure as createCategory)
 * @returns {Promise<Object>}
 */
export const updateCategory = async (id, data) => {
  try {
    const response = await axiosInstance.put(ENDPOINTS.DETAIL(id), data);
    return response.data;
  } catch (error) {
    console.error('updateCategory Error:', error);
    throw error;
  }
};

/**
 * Delete category
 * @param {number} id - Category ID
 * @returns {Promise<Object>}
 */
export const deleteCategory = async (id) => {
  try {
    const response = await axiosInstance.delete(ENDPOINTS.DETAIL(id));
    return response.data;
  } catch (error) {
    console.error('deleteCategory Error:', error);
    throw error;
  }
};

/**
 * Get categories as options for Select dropdown
 * @param {boolean} includeInactive - Include inactive categories
 * @returns {Promise<Array>} Array of { value: id, label: name, children: [...] }
 */
export const getCategoriesAsOptions = async (includeInactive = false) => {
  try {
    const response = await getCategoryTree();
    
    if (!response.success || !response.data) {
      return [];
    }
    
    const mapToOptions = (categories) => {
      return categories
        .filter(cat => includeInactive || cat.is_active)
        .map(cat => ({
          value: cat.id,
          label: cat.name,
          disabled: !cat.is_active,
          children: cat.children && cat.children.length > 0 
            ? mapToOptions(cat.children) 
            : undefined,
        }));
    };
    
    return mapToOptions(response.data);
  } catch (error) {
    console.error('getCategoriesAsOptions Error:', error);
    return [];
  }
};

/**
 * Flatten category tree to flat array
 * @param {Array} tree - Category tree
 * @param {number} level - Current level (for indentation)
 * @returns {Array} Flat array with level indicator
 */
export const flattenCategoryTree = (tree, level = 0) => {
  let result = [];
  
  tree.forEach(category => {
    result.push({
      ...category,
      level,
      indent: '—'.repeat(level), // Visual indent for display
    });
    
    if (category.children && category.children.length > 0) {
      result = result.concat(flattenCategoryTree(category.children, level + 1));
    }
  });
  
  return result;
};

export const hardDeleteCategory = async (id) => {
  const response = await axiosInstance.delete(`/product-categories/${id}/hard`);
  return response.data;
}
export const restoreCategory = async (id) => {
  const response = await axiosInstance.put(`/product-categories/${id}/restore`);
  return response.data;
}


/**
 * Export all API functions as default
 */
const categoryApi = {
  getCategories,
  getCategoryTree,
  getCategoryDetail,
  createCategory,
  updateCategory,
  deleteCategory,
  getCategoriesAsOptions,
  flattenCategoryTree,
  hardDeleteCategory,
  restoreCategory
};

export default categoryApi;
