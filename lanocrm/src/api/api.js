import axiosInstance from './axios';

// Re-export axios instance as 'api' for consistency with other API files
export const api = axiosInstance;

export default axiosInstance;
