/**
 * Application Constants
 * @file src/utils/constants.js
 * @description Enums, options, and constant values for Products module
 */

/**
 * Product Types
 * @type {Array<{value: string, label: string}>}
 */
export const PRODUCT_TYPES = [
  { value: 'single', label: 'Hàng hóa thường' },
  { value: 'combo', label: 'Combo/Gói' },
  { value: 'service', label: 'Dịch vụ' },
];

/**
 * Product Status
 * @type {Array<{value: string, label: string, color: string}>}
 */
export const PRODUCT_STATUS = [
  { value: 'active', label: 'Đang kinh doanh', color: 'success' },
  { value: 'inactive', label: 'Ngưng kinh doanh', color: 'default' },
  { value: 'out_of_stock', label: 'Hết hàng', color: 'error' },
];

/**
 * Stock Status for Filters
 * @type {Array<{value: string, label: string}>}
 */
export const STOCK_STATUS = [
  { value: 'all', label: 'Tất cả' },
  { value: 'in_stock', label: 'Còn hàng' },
  { value: 'low_stock', label: 'Sắp hết' },
  { value: 'out_of_stock', label: 'Hết hàng' },
];

/**
 * Sort Field Options
 * @type {Array<{value: string, label: string}>}
 */
export const SORT_OPTIONS = [
  { value: 'p.id', label: 'Mã hàng' },
  { value: 'p.name', label: 'Tên hàng' },
  { value: 'p.price', label: 'Giá bán' },
  { value: 'p.cost_price', label: 'Giá vốn' },
  { value: 'p.stock_quantity', label: 'Tồn kho' },
  { value: 'p.created_at', label: 'Thời gian tạo' },
];

/**
 * Sort Order Options
 * @type {Array<{value: string, label: string}>}
 */
export const ORDER_OPTIONS = [
  { value: 'asc', label: 'Tăng dần' },
  { value: 'desc', label: 'Giảm dần' },
];

/**
 * Pagination Page Size Options
 * @type {Array<number>}
 */
export const PAGE_SIZE_OPTIONS = [20, 50, 100, 200];

/**
 * Default Pagination State
 * @type {Object}
 */
export const DEFAULT_PAGINATION = {
  page: 1,
  limit: 20,
  total: 0,
  total_pages: 0,
};

/**
 * Product Module Permissions
 * @type {Object}
 */
export const PRODUCT_PERMISSIONS = {
  VIEW: 'products.view',
  CREATE: 'products.create',
  EDIT: 'products.edit',
  DELETE: 'products.delete',
  IMPORT: 'products.import',
  EXPORT: 'products.export',
};

/**
 * Category Module Permissions
 * @type {Object}
 */
export const CATEGORY_PERMISSIONS = {
  VIEW: 'product_categories.view',
  CREATE: 'product_categories.create',
  EDIT: 'product_categories.edit',
  DELETE: 'product_categories.delete',
};

/**
 * Max File Size for Images (5MB)
 * @type {number}
 */
export const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

/**
 * Allowed Image MIME Types
 * @type {Array<string>}
 */
export const ALLOWED_IMAGE_TYPES = [
  'image/jpeg',
  'image/png',
  'image/jpg',
  'image/webp',
];

/**
 * Allowed Image Extensions (for display)
 * @type {Array<string>}
 */
export const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

/**
 * Max Images per Product
 * @type {number}
 */
export const MAX_IMAGES_PER_PRODUCT = 5;

/**
 * Weight Units
 * @type {Array<{value: string, label: string}>}
 
export const WEIGHT_UNITS = [
  { value: 'g', label: 'Gram (g)' },
  { value: 'kg', label: 'Kilogram (kg)' },
  { value: 'mg', label: 'Milligram (mg)' },
];

/**
 * Warranty Units
 * @type {Array<{value: string, label: string}>}
 
export const WARRANTY_UNITS = [
  { value: 'day', label: 'Ngày' },
  { value: 'month', label: 'Tháng' },
  { value: 'year', label: 'Năm' },
];

*/

/**
 * Default Product Form Values
 * @type {Object}
 */
export const DEFAULT_PRODUCT_FORM = {
  code: '',
  name: '',
  category_id: null,
  product_type: 'single',
  unit: 'Cái',
  price: 0,
  cost_price: null,
  wholesale_price: null,
  stock_quantity: null,
  min_stock_threshold: null,
  max_stock_threshold: 999999999,
  barcode: null,
  brand: null,
  weight: null,
  //weight_unit: 'g',
  description: null,
  notes: null,
  warranty_period: null,
  //warranty_unit: 'month',
  is_active: true,
  is_featured: false,
  is_sellable: true,
  meta_title: null,
  meta_description: null,
  meta_keywords: null,
};

/**
 * Default Filter Values
 * @type {Object}
 */
export const DEFAULT_FILTERS = {
  search: '',
  category_id: null,
  product_type: null,
  status: null,
  is_active: null,
  brand: '',
  price_from: null,
  price_to: null,
  stock_from: null,
  stock_to: null,
  sort_by: 'p.created_at',
  order: 'desc',
};

/**
 * Toast Notification Messages
 * @type {Object}
 */
export const TOAST_MESSAGES = {
  PRODUCT: {
    CREATE_SUCCESS: 'Tạo sản phẩm thành công!',
    CREATE_ERROR: 'Tạo sản phẩm thất bại. Vui lòng thử lại.',
    UPDATE_SUCCESS: 'Cập nhật sản phẩm thành công!',
    UPDATE_ERROR: 'Cập nhật sản phẩm thất bại. Vui lòng thử lại.',
    DELETE_SUCCESS: 'Xóa sản phẩm thành công!',
    DELETE_ERROR: 'Xóa sản phẩm thất bại. Vui lòng thử lại.',
    DELETE_CONFIRM: 'Bạn có chắc muốn xóa sản phẩm này?',
    IMPORT_SUCCESS: 'Import sản phẩm thành công!',
    IMPORT_ERROR: 'Import sản phẩm thất bại. Vui lòng kiểm tra file.',
    EXPORT_SUCCESS: 'Xuất file thành công!',
    EXPORT_ERROR: 'Xuất file thất bại. Vui lòng thử lại.',
    CODE_EXISTS: 'Mã sản phẩm đã tồn tại!',
    LOAD_ERROR: 'Tải danh sách sản phẩm thất bại.',
  },
  CATEGORY: {
    CREATE_SUCCESS: 'Tạo nhóm hàng thành công!',
    CREATE_ERROR: 'Tạo nhóm hàng thất bại. Vui lòng thử lại.',
    UPDATE_SUCCESS: 'Cập nhật nhóm hàng thành công!',
    UPDATE_ERROR: 'Cập nhật nhóm hàng thất bại. Vui lòng thử lại.',
    DELETE_SUCCESS: 'Xóa nhóm hàng thành công!',
    DELETE_ERROR: 'Xóa nhóm hàng thất bại. Vui lòng thử lại.',
    DELETE_CONFIRM: 'Bạn có chắc muốn xóa nhóm hàng này?',
    LOAD_ERROR: 'Tải danh sách nhóm hàng thất bại.',
  },
  COMMON: {
    PERMISSION_DENIED: 'Bạn không có quyền thực hiện hành động này.',
    NETWORK_ERROR: 'Lỗi kết nối mạng. Vui lòng kiểm tra internet.',
    UNKNOWN_ERROR: 'Đã xảy ra lỗi không xác định.',
  },
};

/**
 * API Endpoints Base (relative to axios baseURL)
 * @type {Object}
 */
export const API_ENDPOINTS = {
  PRODUCTS: '/products',
  CATEGORIES: '/product-categories',
};

export default {
  PRODUCT_TYPES,
  PRODUCT_STATUS,
  STOCK_STATUS,
  SORT_OPTIONS,
  ORDER_OPTIONS,
  PAGE_SIZE_OPTIONS,
  DEFAULT_PAGINATION,
  PRODUCT_PERMISSIONS,
  CATEGORY_PERMISSIONS,
  MAX_IMAGE_SIZE,
  ALLOWED_IMAGE_TYPES,
  ALLOWED_IMAGE_EXTENSIONS,
  MAX_IMAGES_PER_PRODUCT,
  WEIGHT_UNITS,
  WARRANTY_UNITS,
  DEFAULT_PRODUCT_FORM,
  DEFAULT_FILTERS,
  TOAST_MESSAGES,
  API_ENDPOINTS,
};