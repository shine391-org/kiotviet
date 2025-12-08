/**
 * ProductCreatePage Component Tests
 * @file src/pages/products/ProductCreatePage.test.jsx
 * @description Comprehensive tests for ProductCreatePage component
 */

import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import '@testing-library/jest-dom';
import ProductCreatePage from './ProductCreatePage';
import { usePermission } from '../../utils/usePermission';
import { resetSuccessFlags } from '../../store/slices/productSlice';

// Mock dependencies
import { vi } from 'vitest';

// Mock react-router-dom
const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

// Mock usePermission
vi.mock('../../utils/usePermission');
vi.mock('../../store/slices/productSlice');

// Mock ProductForm
vi.mock('../../components/products/ProductForm', () => ({
  default: ({ mode, onSuccess, onCancel }) => (
    <div data-testid="product-form">
      <div data-testid="form-mode">{mode}</div>
      <button data-testid="form-success" onClick={onSuccess}>
        Success
      </button>
      <button data-testid="form-cancel" onClick={onCancel}>
        Cancel
      </button>
    </div>
  ),
}));

// Mock antd
vi.mock('antd', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    message: {
      success: vi.fn(),
      error: vi.fn(),
    },
    App: {
      useApp: () => ({
        message: {
          success: vi.fn(),
          error: vi.fn(),
        },
      }),
    },
  };
});

// Create mock store
const createMockStore = (initialState = {}) => {
  return configureStore({
    reducer: {
      product: (state = { createSuccess: false }, action) => {
        if (action.type === 'product/resetSuccessFlags') {
          return { ...state, createSuccess: false };
        }
        if (action.type === 'product/createProduct/fulfilled') {
          return { ...state, createSuccess: true };
        }
        return state;
      },
    },
    preloadedState: initialState,
  });
};

// Test wrapper component
const TestWrapper = ({ children, store }) => (
  <Provider store={store}>
    <BrowserRouter>
      <ConfigProvider>
        {children}
      </ConfigProvider>
    </BrowserRouter>
  </Provider>
);

describe('ProductCreatePage Component', () => {
  let mockStore;

  beforeEach(() => {
    vi.clearAllMocks();
    mockStore = createMockStore();
    mockNavigate.mockClear();
    
    // Mock default permission
    usePermission.mockReturnValue({
      hasPermission: vi.fn(() => true),
    });

    // Mock resetSuccessFlags
    resetSuccessFlags.mockReturnValue({ type: 'product/resetSuccessFlags' });
  });

  describe('Component Rendering', () => {
    test('renders page with correct title and elements', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(screen.getByText('Thêm sản phẩm mới')).toBeInTheDocument();
      expect(screen.getByText('Quay lại')).toBeInTheDocument();
      expect(screen.getByTestId('product-form')).toBeInTheDocument();
      expect(screen.getByTestId('form-mode')).toHaveTextContent('create');
    });

    test('renders breadcrumb navigation', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(screen.getByText('Dashboard')).toBeInTheDocument();
      expect(screen.getByText('Sản phẩm')).toBeInTheDocument();
      expect(screen.getByText('Thêm mới')).toBeInTheDocument();
    });

    test('renders ProductForm with correct props', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const productForm = screen.getByTestId('product-form');
      expect(productForm).toBeInTheDocument();
      expect(screen.getByTestId('form-mode')).toHaveTextContent('create');
    });
  });

  describe('Permission Check', () => {
    test('redirects to products list when user lacks create permission', async () => {
      const mockHasPermission = vi.fn(() => false);
      usePermission.mockReturnValue({
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/products');
      });
    });

    test('stays on page when user has create permission', () => {
      const mockHasPermission = vi.fn(() => true);
      usePermission.mockReturnValue({
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(mockNavigate).not.toHaveBeenCalled();
      expect(screen.getByText('Thêm sản phẩm mới')).toBeInTheDocument();
    });
  });

  describe('Navigation Actions', () => {
    test('navigates back to products list when back button is clicked', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const backButton = screen.getByText('Quay lại');
      fireEvent.click(backButton);

      expect(mockNavigate).toHaveBeenCalledWith('/products');
    });

    test('navigates back to products list when form cancel is clicked', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const cancelButton = screen.getByTestId('form-cancel');
      fireEvent.click(cancelButton);

      expect(mockNavigate).toHaveBeenCalledWith('/products');
    });

    test('navigates to dashboard when dashboard breadcrumb is clicked', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const dashboardBreadcrumb = screen.getByText('Dashboard');
      fireEvent.click(dashboardBreadcrumb);

      expect(mockNavigate).toHaveBeenCalledWith('/dashboard');
    });

    test('navigates to products list when products breadcrumb is clicked', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const productsBreadcrumb = screen.getByText('Sản phẩm');
      fireEvent.click(productsBreadcrumb);

      expect(mockNavigate).toHaveBeenCalledWith('/products');
    });
  });

  describe('Success Handling', () => {
    test('shows success message and redirects when createSuccess is true', async () => {
      // Create store with createSuccess: true
      const storeWithSuccess = createMockStore({ 
        product: { createSuccess: true } 
      });

      render(
        <TestWrapper store={storeWithSuccess}>
          <ProductCreatePage />
        </TestWrapper>
      );

      // Wait for useEffect to process createSuccess
      await waitFor(() => {
        expect(resetSuccessFlags).toHaveBeenCalled();
      });

      // Should redirect after 1.5 seconds
      await waitFor(
        () => {
          expect(mockNavigate).toHaveBeenCalledWith('/products');
        },
        { timeout: 2000 }
      );
    });

    test('does not redirect when createSuccess is false', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      // Should not redirect immediately
      expect(mockNavigate).not.toHaveBeenCalled();
    });

    test('calls resetSuccessFlags when createSuccess is true', async () => {
      const storeWithSuccess = createMockStore({ 
        product: { createSuccess: true } 
      });

      render(
        <TestWrapper store={storeWithSuccess}>
          <ProductCreatePage />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(resetSuccessFlags).toHaveBeenCalled();
      });
    });
  });

  describe('Form Integration', () => {
    test('passes correct mode to ProductForm', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(screen.getByTestId('form-mode')).toHaveTextContent('create');
    });

    test('handles form success callback', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const successButton = screen.getByTestId('form-success');
      fireEvent.click(successButton);

      // Success handling is done via Redux, so no immediate navigation expected
      expect(mockNavigate).not.toHaveBeenCalled();
    });

    test('handles form cancel callback', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const cancelButton = screen.getByTestId('form-cancel');
      fireEvent.click(cancelButton);

      expect(mockNavigate).toHaveBeenCalledWith('/products');
    });
  });

  describe('Component Lifecycle', () => {
    test('checks permission on mount', async () => {
      const mockHasPermission = vi.fn(() => true);
      usePermission.mockReturnValue({
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(mockHasPermission).toHaveBeenCalledWith('products.create');
    });

    test('does not redirect if permission check passes', () => {
      const mockHasPermission = vi.fn(() => true);
      usePermission.mockReturnValue({
        hasPermission: mockHasPermission,
      });

      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      expect(mockNavigate).not.toHaveBeenCalled();
    });
  });

  describe('Error Handling', () => {
    test('handles navigation gracefully', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const backButton = screen.getByText('Quay lại');

      // Should not throw error
      expect(() => {
        fireEvent.click(backButton);
      }).not.toThrow();
    });
  });

  describe('Accessibility', () => {
    test('has proper heading structure', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const heading = screen.getByRole('heading', { level: 1 });
      expect(heading).toHaveTextContent('Thêm sản phẩm mới');
    });

    test('buttons are accessible', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      const backButton = screen.getByRole('button', { name: 'Quay lại' });
      expect(backButton).toBeInTheDocument();
      expect(backButton).toBeEnabled();
    });
  });

  describe('Component Structure', () => {
    test('renders within proper container structure', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      // Check that main elements are present
      expect(screen.getByText('Thêm sản phẩm mới')).toBeInTheDocument();
      expect(screen.getByTestId('product-form')).toBeInTheDocument();
      expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });

    test('maintains consistent layout', () => {
      const { container } = render(
        <TestWrapper store={mockStore}>
          <ProductCreatePage />
        </TestWrapper>
      );

      // Check that the component renders without layout issues
      expect(container.firstChild).toBeInTheDocument();
    });
  });
});
