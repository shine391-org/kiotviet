/**
 * Test Helpers for Real API Integration
 * Used by both unit tests and E2E tests
 * 
 * IMPORTANT: These helpers make REAL API calls to the test backend.
 * No mocking is allowed per TESTING-RULES.md
 */

import axios from 'axios';

const TEST_API_BASE = process.env.VITE_API_URL || 'http://localhost:8000/api';

// Create axios instance for test API calls
const testApi = axios.create({
  baseURL: TEST_API_BASE,
  timeout: 10000,
});

// Token key must match the one used in src/api/axios.js
const TOKEN_KEY = 'lano_token';

// Set auth token for authenticated requests
let authToken = null;

export function setTestAuthToken(token) {
  authToken = token;
  testApi.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  // Also set localStorage so components using their own axios instance get the token
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(TOKEN_KEY, token);
  }
}

export function clearTestAuthToken() {
  authToken = null;
  delete testApi.defaults.headers.common['Authorization'];
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem(TOKEN_KEY);
  }
}

/**
 * Login as test user and get auth token
 */
export async function loginTestUser(username = 'devadmin', password = '123aA@hai') {
  try {
    const response = await testApi.post('/auth/login', { username, password });
    if (response.data?.token) {
      setTestAuthToken(response.data.token);
      return response.data;
    }
    throw new Error('No token in response');
  } catch (error) {
    console.error('Test login failed:', error.message);
    throw error;
  }
}

/**
 * Customer API helpers
 */
export const customerTestApi = {
  async getAll(params = {}) {
    const response = await testApi.get('/customers', { params });
    return response.data;
  },

  async getById(id) {
    const response = await testApi.get(`/customers/${id}`);
    return response.data;
  },

  async create(data) {
    const response = await testApi.post('/customers', data);
    return response.data;
  },

  async update(id, data) {
    const response = await testApi.put(`/customers/${id}`, data);
    return response.data;
  },

  async delete(id) {
    const response = await testApi.delete(`/customers/${id}`);
    return response.data;
  },
};

/**
 * Invoice API helpers
 */
export const invoiceTestApi = {
  async getAll(params = {}) {
    const response = await testApi.get('/invoices', { params });
    return response.data;
  },

  async getById(id) {
    const response = await testApi.get(`/invoices/${id}`);
    return response.data;
  },

  async create(data) {
    const response = await testApi.post('/invoices', data);
    return response.data;
  },

  async update(id, data) {
    const response = await testApi.put(`/invoices/${id}`, data);
    return response.data;
  },

  async cancel(id) {
    const response = await testApi.post(`/invoices/${id}/cancel`);
    return response.data;
  },

  async delete(id) {
    const response = await testApi.delete(`/invoices/${id}`);
    return response.data;
  },
};

/**
 * Branch API helpers
 */
export const branchTestApi = {
  async getAll() {
    const response = await testApi.get('/branches');
    return response.data;
  },
};

export { testApi };
