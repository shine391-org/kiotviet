/**
 * Location API Service
 * @file src/api/locationApi.js
 * @description API calls for Vietnam administrative divisions (provinces, districts, wards)
 */

import axiosInstance from './axios';

const BASE = '/locations';

const locationApi = {
  /**
   * Get all provinces
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  getProvinces: async (params = {}) => {
    const response = await axiosInstance.get(`${BASE}/provinces`, {
      params: {
        search: params.search || undefined,
      },
    });
    return response.data;
  },

  /**
   * Get districts by province
   * @param {number} provinceId - Province ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  getDistricts: async (provinceId, params = {}) => {
    const response = await axiosInstance.get(`${BASE}/provinces/${provinceId}/districts`, {
      params: {
        search: params.search || undefined,
      },
    });
    return response.data;
  },

  /**
   * Get wards by district
   * @param {number} districtId - District ID
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>}
   */
  getWards: async (districtId, params = {}) => {
    const response = await axiosInstance.get(`${BASE}/districts/${districtId}/wards`, {
      params: {
        search: params.search || undefined,
      },
    });
    return response.data;
  },

  /**
   * Get location statistics
   * @returns {Promise<Object>}
   */
  getStats: async () => {
    const response = await axiosInstance.get(`${BASE}/stats`);
    return response.data;
  },
};

export default locationApi;
