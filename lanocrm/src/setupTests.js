// jest-dom adds custom jest matchers for asserting on DOM nodes.
// allows you to do things like:
// expect(element).toHaveTextContent(/react/i)
// learn more: https://github.com/testing-library/jest-dom
import '@testing-library/jest-dom';
import { beforeAll, afterAll } from 'vitest';
import { loginTestUser, clearTestAuthToken } from './test-helpers/api.js';

// Polyfill matchMedia for Ant Design components in tests
if (typeof window !== 'undefined' && !window.matchMedia) {
  window.matchMedia = (query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  });
}

// Polyfill ResizeObserver for jsdom tests (used by rc-resize-observer in Ant Design)
if (typeof window !== 'undefined' && !window.ResizeObserver) {
  window.ResizeObserver = class ResizeObserver {
    constructor(callback) {
      this.callback = callback;
    }
    observe() {
      // no-op in test environment
    }
    unobserve() {
      // no-op in test environment
    }
    disconnect() {
      // no-op in test environment
    }
  };
}

// Set flag to prevent 401 redirect during tests
if (typeof window !== 'undefined') {
  window.__E2E_TEST__ = true;
}

// Login once before all tests (real API call to get auth token)
// Increase timeout to 30s since network requests may be slow
beforeAll(async () => {
  try {
    await loginTestUser('devadmin', '123aA@hai');
    console.log('✅ Test auth token set successfully');
  } catch (error) {
    console.warn('⚠️ Could not login for tests - API calls may fail with 401:', error.message);
    // Don't throw - let individual tests handle auth failures if backend is not running
  }
}, 30000);

// Cleanup after all tests
afterAll(() => {
  clearTestAuthToken();
});
