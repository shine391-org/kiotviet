// @vitest-environment node
import { describe, it, expect, beforeAll, afterAll, vi } from 'vitest';

// Mock axios instance to avoid hitting real backend (CI lacks writable cache)
const postMock = vi.fn();
vi.mock('./axios', () => ({
  default: {
    post: (...args) => postMock(...args),
  },
}));

// Important: set API base URL before importing axios/authApi
const API_BASE_URL = process.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
process.env.VITE_API_BASE_URL = API_BASE_URL;
process.env.VITE_TOKEN_KEY = 'test_token';

// Lazy import after env vars set
let authApi;

// Simple localStorage polyfill for node env
const store = new Map();
global.localStorage = {
  setItem: (k, v) => store.set(k, String(v)),
  getItem: (k) => (store.has(k) ? store.get(k) : null),
  removeItem: (k) => store.delete(k),
  clear: () => store.clear(),
};

// Minimal window stub for axios interceptor
global.window = {
  location: { pathname: '/', href: '/' },
};

describe('authApi login (integration)', () => {
  beforeAll(async () => {
    // increase timeout for network
    vi.setConfig({ testTimeout: 15000 });

    // Prepare mock response for login
    postMock.mockResolvedValue({
      data: {
        token: 'mock-token-123',
        user: {
          id: 1,
          username: 'devadmin',
          permissions: ['*'],
        },
      },
    });

    authApi = (await import('./authApi')).default;
    localStorage.clear();
  });

  afterAll(() => {
    localStorage.clear();
  });

  it('logs in with devadmin credentials and stores token', async () => {
    const result = await authApi.login({ username: 'devadmin', password: 'Admin@123' });

    expect(result.success).toBe(true);
    expect(result.token).toBeTruthy();
    expect(result.user?.username).toBe('devadmin');

    const storedToken = localStorage.getItem('lano_token');
    expect(storedToken).toBeTruthy();

    const storedUser = JSON.parse(localStorage.getItem('lano_user'));
    expect(storedUser?.username).toBe('devadmin');
  });
});
