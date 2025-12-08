/**
 * Test Render Utilities
 * Provides consistent rendering for React components in tests
 */

import React from 'react';
import { render } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { BrowserRouter, MemoryRouter } from 'react-router-dom';
import { App as AntdApp, ConfigProvider } from 'antd';
import viVN from 'antd/locale/vi_VN';

// Import all reducers
import customerReducer from '../store/slices/customerSlice';
import invoiceReducer from '../store/slices/invoiceSlice';
import branchReducer from '../store/slices/branchSlice';
import authReducer from '../store/slices/authSlice';

/**
 * Create a test store with all reducers
 */
export function createTestStore(preloadedState = {}) {
  return configureStore({
    reducer: {
      customer: customerReducer,
      invoices: invoiceReducer,
      branch: branchReducer,
      auth: authReducer,
    },
    preloadedState,
  });
}

/**
 * Render component with all providers
 * Uses real Redux store (no mocking)
 */
export function renderWithProviders(
  ui,
  {
    preloadedState = {},
    store = createTestStore(preloadedState),
    route = '/',
    ...renderOptions
  } = {}
) {
  function Wrapper({ children }) {
    return (
      <Provider store={store}>
        <ConfigProvider locale={viVN}>
          <AntdApp>
            <MemoryRouter initialEntries={[route]}>
              {children}
            </MemoryRouter>
          </AntdApp>
        </ConfigProvider>
      </Provider>
    );
  }

  return {
    store,
    ...render(ui, { wrapper: Wrapper, ...renderOptions }),
  };
}

export { render };
