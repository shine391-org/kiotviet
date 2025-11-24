import axios from 'axios';

// Default to same-origin backend (docker-compose exposes /backend-ci)
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';
const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || 'lano_token';

// Tạo axios instance
const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - thêm token vào mọi request
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    // ✅ DEBUG: Log request
    console.log('🔵 API Request:', {
      method: config.method.toUpperCase(),
      url: config.url,
      params: config.params,
      hasToken: !!token
    });
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - xử lý lỗi chung
axiosInstance.interceptors.response.use(
  (response) => {
    // ✅ DEBUG: Log success
    console.log('🟢 API Response:', {
      status: response.status,
      url: response.config.url
    });
    return response;
  },
  (error) => {
    // ✅ DEBUG: Log error
    console.error('🔴 API Error:', {
      status: error.response?.status,
      url: error.config?.url,
      message: error.response?.data?.message || error.message,
      data: error.response?.data
    });

    if (error.response) {
      let errorMessage = 'Có lỗi xảy ra';
      
      switch (error.response.status) {
        case 400:
          errorMessage = error.response.data?.message || 'Dữ liệu không hợp lệ';
          break;
          
        case 401:
          errorMessage = 'Token hết hạn, vui lòng đăng nhập lại';
          if (!window.__E2E_TEST__ && window.location.pathname !== '/login') {
            localStorage.removeItem(TOKEN_KEY);
            window.location.href = '/login';
          }
          break;
          
        case 403:
          errorMessage = 'Bạn không có quyền thực hiện hành động này';
          break;
          
        case 404:
          errorMessage = error.response.data?.message || 'Không tìm thấy tài nguyên';
          console.warn('⚠️ 404 - Endpoint không tồn tại:', error.config?.url);
          break;
          
        case 500:
          errorMessage = error.response.data?.message || 'Lỗi máy chủ nội bộ';
          break;
          
        default:
          errorMessage = error.response.data?.message || 'Có lỗi xảy ra';
      }
      
      error.message = errorMessage;
    } else if (error.request) {
      error.message = 'Lỗi kết nối với server';
      console.error('🔴 Không có response từ server:', error.request);
    } else {
      error.message = 'Lỗi không xác định: ' + error.message;
    }
    
    return Promise.reject(error);
  }
);

export default axiosInstance;
