TASK-002-FE: FRONTEND INTEGRATION FOR SKU VALIDATION
CONTEXT
Backend đã hoàn thành Task 1 với các thay đổi:

✅ Cross-table validation (Product ↔ Variant)

✅ Enhanced error messages

✅ Improved checkCode() API response

Backend PR: [Link to BE PR]

OBJECTIVE
Integrate Backend Task 1 changes vào Frontend để:

Hiển thị error messages chi tiết từ backend

Improve UX với centralized error handling

Đảm bảo backward compatibility

STATUS (22/11/2025)
- [x] Centralized error handler added
- [x] Product & variant forms use backend messages
- [ ] Manual UX checks on dev/staging

REQUIREMENTS
Requirement 1: Centralized Error Handler
Tạo utility function để handle API errors một cách nhất quán.

Benefits:

DRY (Don't Repeat Yourself)

Consistent error display across app

Easy to update error handling logic in one place

Requirement 2: Enhanced Error Display
Update Product & Variant forms để hiển thị error messages từ backend.

Error Messages Examples:

"Mã sản phẩm đã tồn tại trong danh sách sản phẩm"

"Mã sản phẩm đã tồn tại trong danh sách phiên bản"

"SKU đã tồn tại trong danh sách sản phẩm"

"SKU đã tồn tại trong danh sách phiên bản"

IMPLEMENTATION PLAN
Phase 1: Create Centralized Error Handler
File: lanocrm/src/utils/apiErrorHandler.js (TẠO MỚI)

javascript
/**
 * Centralized API error handler
 * @agent-pattern: DRY error handling with priority fallback
 * @agent-use: Import in all API-calling components
 */

import { toast } from 'vue-toastification'; // Hoặc notification lib của bạn

/**
 * Handle API errors with priority message extraction
 * @param {Error} error - Axios error object
 * @param {Object} options - Display options
 * @returns {string} - Error message displayed
 */
export function handleApiError(error, options = {}) {
  const {
    duration = 5000,
    showToast = true,
    defaultMessage = 'Có lỗi xảy ra, vui lòng thử lại'
  } = options;

  // Priority 1: Backend validation message
  let message = error.response?.data?.message;
  
  // Priority 2: Backend error message
  if (!message) {
    message = error.response?.data?.error;
  }
  
  // Priority 3: Network error message
  if (!message) {
    message = error.message;
  }
  
  // Priority 4: Default fallback
  if (!message) {
    message = defaultMessage;
  }

  // Display toast notification
  if (showToast) {
    toast.error(message, {
      duration,
      position: 'top-right'
    });
  }

  // Log to console for debugging
  if (process.env.NODE_ENV === 'development') {
    console.error('[API Error]', {
      message,
      status: error.response?.status,
      data: error.response?.data,
      fullError: error
    });
  }

  return message;
}

/**
 * Handle API success with toast
 * @param {string} message - Success message
 */
export function handleApiSuccess(message, options = {}) {
  const { duration = 3000 } = options;
  
  toast.success(message, {
    duration,
    position: 'top-right'
  });
}

/**
 * Validate form before submit
 * @param {Object} formData - Form data to validate
 * @param {Array} requiredFields - Required field names
 * @returns {boolean} - Is valid
 */
export function validateRequired(formData, requiredFields) {
  for (const field of requiredFields) {
    if (!formData[field] || formData[field].toString().trim() === '') {
      toast.error(`Vui lòng nhập ${field}`);
      return false;
    }
  }
  return true;
}

export default {
  handleApiError,
  handleApiSuccess,
  validateRequired
};
Phase 2: Update Product Form
File: lanocrm/src/pages/Products/ProductForm.vue (CẬP NHẬT)

text
<template>
  <div class="product-form">
    <h2>{{ isEditMode ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm mới' }}</h2>
    
    <form @submit.prevent="handleSubmit">
      <!-- Mã sản phẩm -->
      <div class="form-group">
        <label class="required">Mã sản phẩm</label>
        <input
          v-model="formData.code"
          type="text"
          placeholder="VD: SP001"
          required
          :disabled="isSubmitting"
        />
      </div>

      <!-- Tên sản phẩm -->
      <div class="form-group">
        <label class="required">Tên sản phẩm</label>
        <input
          v-model="formData.name"
          type="text"
          placeholder="Nhập tên sản phẩm"
          required
          :disabled="isSubmitting"
        />
      </div>

      <!-- ... Other fields ... -->

      <!-- Submit buttons -->
      <div class="form-actions">
        <button 
          type="button" 
          @click="handleCancel"
          :disabled="isSubmitting"
        >
          Hủy
        </button>
        <button 
          type="submit" 
          class="primary"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Đang xử lý...' : (isEditMode ? 'Cập nhật' : 'Tạo mới') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';
import { handleApiError, handleApiSuccess, validateRequired } from '@/utils/apiErrorHandler';

const router = useRouter();
const route = useRoute();

const isEditMode = ref(false);
const isSubmitting = ref(false);

const formData = ref({
  code: '',
  name: '',
  description: '',
  purchase_price: 0,
  selling_price: 0,
  // ... other fields
});

// ✅ Handle form submission with centralized error handling
const handleSubmit = async () => {
  // Validate required fields
  if (!validateRequired(formData.value, ['code', 'name'])) {
    return;
  }

  isSubmitting.value = true;

  try {
    if (isEditMode.value) {
      // Update existing product
      await api.put(`/api/products/${route.params.id}`, formData.value);
      handleApiSuccess('Cập nhật sản phẩm thành công');
    } else {
      // Create new product
      await api.post('/api/products', formData.value);
      handleApiSuccess('Tạo sản phẩm thành công');
    }

    // Navigate back to list
    router.push('/products');
    
  } catch (error) {
    // ✅ Centralized error handling
    handleApiError(error);
    // Error message tự động hiển thị:
    // - "Mã sản phẩm đã tồn tại trong danh sách sản phẩm"
    // - "Mã sản phẩm đã tồn tại trong danh sách phiên bản"
    
  } finally {
    isSubmitting.value = false;
  }
};

const handleCancel = () => {
  router.back();
};

// Load product data if editing
onMounted(async () => {
  if (route.params.id) {
    isEditMode.value = true;
    try {
      const response = await api.get(`/api/products/${route.params.id}`);
      formData.value = response.data.data;
    } catch (error) {
      handleApiError(error);
      router.push('/products');
    }
  }
});
</script>

<style scoped>
.product-form {
  max-width: 800px;
  margin: 0 auto;
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.form-group label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.form-group input:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

button {
  padding: 10px 20px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button.primary {
  background-color: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

button.primary:hover:not(:disabled) {
  background-color: #2563eb;
}
</style>
Phase 3: Update Variant Form
File: lanocrm/src/pages/Products/VariantForm.vue (CẬP NHẬT)

text
<template>
  <div class="variant-form">
    <h2>{{ isEditMode ? 'Cập nhật phiên bản' : 'Tạo phiên bản mới' }}</h2>
    
    <form @submit.prevent="handleSubmit">
      <!-- SKU -->
      <div class="form-group">
        <label class="required">SKU</label>
        <input
          v-model="formData.sku"
          type="text"
          placeholder="VD: VAR001"
          required
          :disabled="isSubmitting"
        />
        <small class="help-text">SKU phải unique trong toàn hệ thống</small>
      </div>

      <!-- Tên phiên bản -->
      <div class="form-group">
        <label class="required">Tên phiên bản</label>
        <input
          v-model="formData.variant_name"
          type="text"
          placeholder="VD: Size M, Màu Đỏ"
          required
          :disabled="isSubmitting"
        />
      </div>

      <!-- Giá -->
      <div class="form-group">
        <label>Giá bán</label>
        <input
          v-model.number="formData.price"
          type="number"
          min="0"
          :disabled="isSubmitting"
        />
      </div>

      <!-- ... Other fields ... -->

      <!-- Submit buttons -->
      <div class="form-actions">
        <button 
          type="button" 
          @click="handleCancel"
          :disabled="isSubmitting"
        >
          Hủy
        </button>
        <button 
          type="submit" 
          class="primary"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? 'Đang xử lý...' : (isEditMode ? 'Cập nhật' : 'Tạo mới') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';
import { handleApiError, handleApiSuccess, validateRequired } from '@/utils/apiErrorHandler';

const router = useRouter();
const route = useRoute();

const isEditMode = ref(false);
const isSubmitting = ref(false);
const productId = ref(null);

const formData = ref({
  sku: '',
  variant_name: '',
  price: 0,
  cost_price: 0,
  stock_quantity: 0,
  // ... other fields
});

// ✅ Handle form submission with centralized error handling
const handleSubmit = async () => {
  // Validate required fields
  if (!validateRequired(formData.value, ['sku', 'variant_name'])) {
    return;
  }

  isSubmitting.value = true;

  try {
    if (isEditMode.value) {
      // Update existing variant
      await api.put(
        `/api/products/${productId.value}/variants/${route.params.variantId}`,
        formData.value
      );
      handleApiSuccess('Cập nhật phiên bản thành công');
    } else {
      // Create new variant
      await api.post(
        `/api/products/${productId.value}/variants`,
        formData.value
      );
      handleApiSuccess('Tạo phiên bản thành công');
    }

    // Navigate back to product detail
    router.push(`/products/${productId.value}`);
    
  } catch (error) {
    // ✅ Centralized error handling
    handleApiError(error);
    // Error message tự động hiển thị:
    // - "SKU đã tồn tại trong danh sách phiên bản"
    // - "SKU đã tồn tại trong danh sách sản phẩm"
    
  } finally {
    isSubmitting.value = false;
  }
};

const handleCancel = () => {
  router.back();
};

// Load variant data if editing
onMounted(async () => {
  productId.value = route.params.productId;
  
  if (route.params.variantId) {
    isEditMode.value = true;
    try {
      const response = await api.get(
        `/api/products/${productId.value}/variants/${route.params.variantId}`
      );
      formData.value = response.data.data;
    } catch (error) {
      handleApiError(error);
      router.push(`/products/${productId.value}`);
    }
  }
});
</script>

<style scoped>
/* Same styles as ProductForm.vue */
.help-text {
  display: block;
  margin-top: 4px;
  color: #6b7280;
  font-size: 12px;
}
</style>
Phase 4: Update API Service (Optional Enhancement)
File: lanocrm/src/services/api.js (CẬP NHẬT)

Thêm interceptor để log errors globally:

javascript
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.VUE_APP_API_URL || 'http://localhost:8080/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if exists
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // ✅ Global error logging
    if (process.env.NODE_ENV === 'development') {
      console.error('[API Response Error]', {
        url: error.config?.url,
        method: error.config?.method,
        status: error.response?.status,
        message: error.response?.data?.message,
        data: error.response?.data
      });
    }

    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }

    return Promise.reject(error);
  }
);

export default api;
TESTING CHECKLIST
Manual Testing
Test Case 1: Create Product - Duplicate Code (Product)

 Tạo Product với code "SP001"

 Tạo Product thứ 2 với code "SP001"

 Expected: Toast hiển thị "Mã sản phẩm đã tồn tại trong danh sách sản phẩm"

 Form không reload, data giữ nguyên

Test Case 2: Create Product - Duplicate Code (Variant)

 Tạo Product có variant với SKU "VAR001"

 Tạo Product mới với code "VAR001"

 Expected: Toast hiển thị "Mã sản phẩm đã tồn tại trong danh sách phiên bản"

Test Case 3: Create Variant - Duplicate SKU (Product)

 Tạo Product với code "SP001"

 Tạo Variant cho product khác với SKU "SP001"

 Expected: Toast hiển thị "SKU đã tồn tại trong danh sách sản phẩm"

Test Case 4: Create Variant - Duplicate SKU (Variant)

 Tạo Variant với SKU "VAR001"

 Tạo Variant thứ 2 với SKU "VAR001"

 Expected: Toast hiển thị "SKU đã tồn tại trong danh sách phiên bản"

Test Case 5: Update Product - Success

 Update product code thành code chưa tồn tại

 Expected: Toast hiển thị "Cập nhật sản phẩm thành công"

 Redirect về /products

Test Case 6: Network Error

 Tắt backend server

 Submit form

 Expected: Toast hiển thị "Có lỗi xảy ra, vui lòng thử lại"

Test Case 7: Validation Error (Empty Fields)

 Submit form với code rỗng

 Expected: Toast hiển thị "Vui lòng nhập code"

 Form không submit

Automated Tests (Optional)
File: lanocrm/tests/unit/apiErrorHandler.spec.js (TẠO MỚI)

javascript
import { describe, it, expect, vi } from 'vitest';
import { handleApiError } from '@/utils/apiErrorHandler';

describe('apiErrorHandler', () => {
  it('should extract message from response.data.message', () => {
    const error = {
      response: {
        data: {
          message: 'Mã sản phẩm đã tồn tại'
        }
      }
    };

    const message = handleApiError(error, { showToast: false });
    expect(message).toBe('Mã sản phẩm đã tồn tại');
  });

  it('should fallback to default message if no message found', () => {
    const error = {};

    const message = handleApiError(error, { showToast: false });
    expect(message).toBe('Có lỗi xảy ra, vui lòng thử lại');
  });
});
FILES SUMMARY
Tạo Mới (1 file)
text
lanocrm/src/
└── utils/
    └── apiErrorHandler.js          ← TẠO MỚI
Cập Nhật (3 files)
text
lanocrm/src/
├── pages/Products/
│   ├── ProductForm.vue             ← CẬP NHẬT
│   └── VariantForm.vue             ← CẬP NHẬT
└── services/
    └── api.js                      ← CẬP NHẬT (optional)
DEFINITION OF DONE
 apiErrorHandler.js được tạo với 3 functions

 ProductForm.vue sử dụng handleApiError()

 VariantForm.vue sử dụng handleApiError()

 7 manual test cases passed

 Error messages hiển thị đúng từ backend

 Form không crash khi có lỗi

 Code review approved

 Merged vào branch dev

DEPLOYMENT CHECKLIST
Pre-deployment:

 Backend Task 1 đã deployed lên staging/production

 Frontend code review passed

 Manual testing completed on dev environment

Deployment:

 Merge PR vào dev branch

 Deploy frontend lên staging

 Smoke test trên staging

 Deploy lên production

 Monitor error logs trong 24h đầu

Post-deployment:

 Thông báo team về deployment

 Update documentation

 Close GitHub issue
