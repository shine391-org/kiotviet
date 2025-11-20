import axiosInstance from './axios';

const branchApi = {
  // Lấy danh sách chi nhánh
  getBranches: async (params = {}) => {
    try {
      const response = await axiosInstance.get('/branches', { params });
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Lấy chi tiết chi nhánh
  getBranchById: async (id) => {
    try {
      const response = await axiosInstance.get(`/branches/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Tạo chi nhánh mới
  createBranch: async (branchData) => {
    try {
      const response = await axiosInstance.post('/branches', branchData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Cập nhật chi nhánh
  updateBranch: async (id, branchData) => {
    try {
      const response = await axiosInstance.put(`/branches/${id}`, branchData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Xóa chi nhánh
  deleteBranch: async (id) => {
    try {
      const response = await axiosInstance.delete(`/branches/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Export chi nhánh ra CSV
  exportBranches: async (params = {}) => {
    try {
      const response = await axiosInstance.get('/branches/export', {
        params,
        responseType: 'blob',
      });
      return response.data;
    } catch (error) {
      throw error;
    }
  },
// Đặt chi nhánh mặc định
setDefaultBranch: async (id) => {
  try {
    const response = await axiosInstance.post(`/branches/set_default/${id}`);
    console.log('✅ Set Default Response:', response.data);
    return response.data;
  } catch (error) {
    console.error('❌ Set Default Error:', error.response?.data || error.message);
    throw error.response?.data?.message || error.message || 'Lỗi khi đặt mặc định';
  }
},

};

export default branchApi;