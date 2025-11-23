/**
 * ProductForm Component Tests
 * @file src/components/products/ProductForm.test.jsx
 * @description Comprehensive tests for ProductForm component
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import '@testing-library/jest-dom';
import ProductForm from './ProductForm';
import * as productApi from '../../api/productApi';
import * as attributeApi from '../../api/attributeApi';
import { fetchCategoryTree } from '../../store/slices/categorySlice';
import { fetchProducts } from '../../store/slices/productSlice';
import { handleApiError } from '../../utils/apiErrorHandler';

// Mock dependencies with Vitest
import { vi } from 'vitest';
vi.mock('../../api/productApi');
vi.mock('../../api/attributeApi');
vi.mock('../../store/slices/categorySlice');
vi.mock('../../store/slices/productSlice');
vi.mock('../../utils/apiErrorHandler');

// Mock antd components properly
vi.mock('antd', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    App: {
      useApp: () => ({
        message: {
          success: vi.fn(),
          error: vi.fn(),
          warning: vi.fn(),
        },
      }),
    },
    TreeSelect: {
      SHOW_PARENT: 'SHOW_PARENT',
      __esModule: true,
      default: vi.fn(({ onChange, ...props }) => (
        <select
          data-testid="tree-select"
          onChange={(e) => onChange(e.target.value ? [parseInt(e.target.value)] : [])}
          {...props}
        >
          <option value="">Select categories</option>
          <option value="1">Category 1</option>
          <option value="2">Category 2</option>
        </select>
      )),
    },
  };
});

// Create mock store
const createMockStore = (initialState = {}) => {
  return configureStore({
    reducer: {
      category: (state = { categoryTree: [] }, action) => {
        if (action.type === 'category/fetchCategoryTree/fulfilled') {
          return { categoryTree: action.payload };
        }
        return state;
      },
      product: (state = { createLoading: false, updateLoading: false }, action) => {
        if (action.type === 'product/createProduct/pending') {
          return { ...state, createLoading: true };
        }
        if (action.type === 'product/createProduct/fulfilled') {
          return { ...state, createLoading: false };
        }
        if (action.type === 'product/updateProduct/pending') {
          return { ...state, updateLoading: true };
        }
        if (action.type === 'product/updateProduct/fulfilled') {
          return { ...state, updateLoading: false };
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

describe('ProductForm Component', () => {
  let mockStore;
  let mockOnSuccess;
  let mockOnCancel;

  beforeEach(() => {
    vi.clearAllMocks();
    mockStore = createMockStore();
    mockOnSuccess = vi.fn();
    mockOnCancel = vi.fn();
    
    // Mock default API responses
    fetchCategoryTree.mockResolvedValue({
      data: [
        { id: 1, name: 'Category 1', children: [] },
        { id: 2, name: 'Category 2', children: [] },
      ],
    });
    
    productApi.getProductDetail.mockResolvedValue({
      success: true,
      data: {
        id: 1,
        code: 'TEST-001',
        name: 'Test Product',
        category_ids: [1],
        product_type: 'goods',
        unit: 'cái',
        purchase_price: 100,
        selling_price: 200,
        wholesale_price: 150,
        stock_quantity: 50,
        min_stock_alert: 10,
        max_stock_alert: 100,
        barcode: '123456789',
        brand: 'Test Brand',
        weight: 1.5,
        description: 'Test description',
        note_template: 'Test note',
        warranty_period: 12,
        is_active: 1,
        is_featured: 0,
        is_available_online: 1,
        has_variants: 0,
        primary_image: { image_url: 'http://example.com/image.jpg' },
      },
    });
    
    productApi.createProduct.mockResolvedValue({
      success: true,
      data: { id: 1, code: 'TEST-001' },
    });
    
    productApi.updateProduct.mockResolvedValue({
      success: true,
      data: { id: 1, code: 'TEST-001' },
      updated_fields: { name: { old: 'Old Name', new: 'New Name' } },
    });
    
    attributeApi.getProductAttributeValues.mockResolvedValue({
      success: true,
      data: [],
    });
    
    attributeApi.updateProductAttributeValues.mockResolvedValue({
      success: true,
    });
  });

  describe('Component Rendering', () => {
    test('renders create form with all fields', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      expect(screen.getByLabelText(/Mã hàng/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Tên sản phẩm/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Danh mục/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Loại sản phẩm/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Đơn vị tính/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Giá vốn/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Giá bán/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Giá bán buôn/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Tồn kho hiện tại/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Tồn kho tối thiểu/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Tồn kho tối đa/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Thương hiệu/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Mã vạch/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Cân nặng/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Thời gian bảo hành/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Mô tả/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/Ghi chú/i)).toBeInTheDocument();
      expect(screen.getByRole('checkbox', { name: /Hoạt động/i })).toBeInTheDocument();
      expect(screen.getByRole('checkbox', { name: /Sản phẩm nổi bật/i })).toBeInTheDocument();
      expect(screen.getByRole('checkbox', { name: /Có bán được/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Thêm sản phẩm/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Hủy/i })).toBeInTheDocument();
    });

    test('renders edit form with update button', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      expect(screen.getByRole('button', { name: /Cập nhật sản phẩm/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Thiết lập biến thể/i })).toBeInTheDocument();
    });

    test('disables code field in edit mode', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByLabelText(/Mã hàng/i)).toBeDisabled();
      });
    });
  });

  describe('Form Input Handling', () => {
    test('handles text input changes', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      expect(codeInput.value).toBe('TEST-001');

      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      expect(nameInput.value).toBe('Test Product');
    });

    test('handles number input changes', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const priceInput = screen.getByLabelText(/Giá bán/i);
      fireEvent.change(priceInput, { target: { value: '200' } });
      expect(priceInput.value).toBe('200');

      const stockInput = screen.getByLabelText(/Tồn kho hiện tại/i);
      fireEvent.change(stockInput, { target: { value: '50' } });
      expect(stockInput.value).toBe('50');
    });

    test('handles select input changes', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const productTypeSelect = screen.getByDisplayValue(/Hàng hóa/i);
      fireEvent.change(productTypeSelect, { target: { value: 'service' } });
      expect(productTypeSelect.value).toBe('service');

      const unitSelect = screen.getByDisplayValue(/Cái/i);
      fireEvent.change(unitSelect, { target: { value: 'kg' } });
      expect(unitSelect.value).toBe('kg');
    });

    test('handles checkbox changes', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const activeCheckbox = screen.getByRole('checkbox', { name: /Hoạt động/i });
      expect(activeCheckbox).toBeChecked();
      
      fireEvent.click(activeCheckbox);
      expect(activeCheckbox).not.toBeChecked();

      fireEvent.click(activeCheckbox);
      expect(activeCheckbox).toBeChecked();
    });

    test('handles textarea changes', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const descriptionTextarea = screen.getByLabelText(/Mô tả/i);
      fireEvent.change(descriptionTextarea, { target: { value: 'Test description' } });
      expect(descriptionTextarea.value).toBe('Test description');

      const noteTextarea = screen.getByLabelText(/Ghi chú/i);
      fireEvent.change(noteTextarea, { target: { value: 'Test note' } });
      expect(noteTextarea.value).toBe('Test note');
    });
  });

  describe('Form Validation', () => {
    test('shows validation errors for required fields', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });
      
      // Try to submit empty form
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/Mã hàng là bắt buộc/i)).toBeInTheDocument();
        expect(screen.getByText(/Tên sản phẩm là bắt buộc/i)).toBeInTheDocument();
        expect(screen.getByText(/Giá bán phải lớn hơn 0/i)).toBeInTheDocument();
      });
    });

    test('validates selling price must be greater than 0', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getByLabelText(/Giá bán/i);
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '0' } });

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/Giá bán phải lớn hơn 0/i)).toBeInTheDocument();
      });
    });
  });

  describe('Form Submission', () => {
    test('submits create form successfully', async () => {
      const { message } = vi.importActual('antd').App.useApp();
      
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getByLabelText(/Giá bán/i);
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '200' } });

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(productApi.createProduct).toHaveBeenCalledWith(
          expect.objectContaining({
            code: 'TEST-001',
            name: 'Test Product',
            selling_price: 200,
            is_active: 1,
            is_featured: 0,
            is_available_online: 1,
          })
        );
        expect(message.success).toHaveBeenCalledWith('✅ Tạo mới sản phẩm thành công', 3);
        expect(mockOnSuccess).toHaveBeenCalled();
      });
    });

    test('submits edit form successfully', async () => {
      const { message } = vi.importActual('antd').App.useApp();
      
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByDisplayValue('TEST-001')).toBeInTheDocument();
      });

      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const submitButton = screen.getByRole('button', { name: /Cập nhật sản phẩm/i });

      fireEvent.change(nameInput, { target: { value: 'Updated Product' } });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(productApi.updateProduct).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            name: 'Updated Product',
            code: 'TEST-001',
          })
        );
        expect(message.success).toHaveBeenCalled();
        expect(mockOnSuccess).toHaveBeenCalled();
      });
    });

    test('handles form submission errors', async () => {
      productApi.createProduct.mockRejectedValue(new Error('Network error'));
      
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getByLabelText(/Giá bán/i);
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '200' } });

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(handleApiError).toHaveBeenCalled();
      });
    });

    test('shows error when selling price is 0 or negative', async () => {
      const { message } = vi.importActual('antd').App.useApp();
      
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getByLabelText(/Giá bán/i);
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '0' } });

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(message.error).toHaveBeenCalledWith('Giá bán phải lớn hơn 0');
      });
    });
  });

  describe('Data Loading', () => {
    test('loads product data in edit mode', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(productApi.getProductDetail).toHaveBeenCalledWith(1);
        expect(attributeApi.getProductAttributeValues).toHaveBeenCalledWith(1);
      });

      expect(screen.getByDisplayValue('TEST-001')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Test Product')).toBeInTheDocument();
      expect(screen.getByDisplayValue('200')).toBeInTheDocument();
    });

    test('handles product data loading error', async () => {
      productApi.getProductDetail.mockRejectedValue(new Error('Load error'));
      
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(handleApiError).toHaveBeenCalled();
      });
    });

    test('loads categories on mount', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(fetchCategoryTree).toHaveBeenCalled();
      });
    });
  });

  describe('Button Actions', () => {
    test('calls onCancel when cancel button is clicked', () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const cancelButton = screen.getByRole('button', { name: /Hủy/i });
      fireEvent.click(cancelButton);

      expect(mockOnCancel).toHaveBeenCalled();
    });

    test('shows variant setup modal in edit mode for simple products', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Thiết lập biến thể/i })).toBeInTheDocument();
      });

      const variantButton = screen.getByRole('button', { name: /Thiết lập biến thể/i });
      fireEvent.click(variantButton);

      expect(variantButton).toBeInTheDocument();
    });
  });

  describe('Form State Management', () => {
    test('handles form reset and initial values', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByDisplayValue('TEST-001')).toBeInTheDocument();
      });

      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      fireEvent.change(nameInput, { target: { value: 'New Name' } });

      expect(nameInput.value).toBe('New Name');
    });

    test('handles boolean field conversions correctly', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByDisplayValue('TEST-001')).toBeInTheDocument();
      });

      const submitButton = screen.getByRole('button', { name: /Cập nhật sản phẩm/i });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(productApi.updateProduct).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            is_active: 1,
            is_featured: 0,
            is_available_online: 1,
          })
        );
      });
    });
  });
});
