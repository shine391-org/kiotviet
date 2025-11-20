// src/api/userApi.js - FIX HOÀN CHỈNH
import axiosInstance from './axios';

const userApi = {
  // Lấy danh sách users
  getUsers: async (params = {}) => {
    try {
      const response = await axiosInstance.get('/users', { params });
      return response.data; // Backend trả về { data: [], pagination: {} }
    } catch (error) {
      throw error;
    }
  },

  // Lấy chi tiết user
  getUserById: async (id) => {
    try {
      // ✅ FIX: Endpoint đúng với backend
      const response = await axiosInstance.get(`/users/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Tạo user mới
  createUser: async (userData) => {
    try {
      // ✅ FIX: Endpoint đúng với backend
      const response = await axiosInstance.post('/users/create', userData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Cập nhật user
  updateUser: async (id, userData) => {
    try {
      // ✅ FIX: Endpoint đúng với backend
      const response = await axiosInstance.put(`/users/update/${id}`, userData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Xóa user
  deleteUser: async (id) => {
    try {
      // ✅ FIX: Endpoint đúng với backend
      const response = await axiosInstance.delete(`/users/delete/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  // Lấy danh sách vai trò
  getRoles: async () => {
    try {
      const response = await axiosInstance.get('/users/roles');
      return response.data;
    } catch (error) {
      throw error;
    }
  },
  
  // Get all branches
  getBranches: async () => {
    const response = await axiosInstance.get('/users/branches');
    return response.data;
  },
  
  // Change password (Admin - NO old password)
  changePassword: async (userId, passwordData) => {
    const response = await axiosInstance.put(`/users/${userId}/change-password`, passwordData);
    return response.data;
  },
};

export default userApi;