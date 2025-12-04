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
import { productSchema } from '../../utils/validators';

// Mock dependencies with Vitest
import { vi } from 'vitest';
vi.mock('../../api/productApi');
vi.mock('../../api/attributeApi');
vi.mock('../../store/slices/categorySlice');
vi.mock('../../store/slices/productSlice');
vi.mock('../../utils/apiErrorHandler');
vi.mock('../../utils/validators', () => ({
  productSchema: {
    validate: vi.fn(async (values) => values),
  },
}));
vi.mock('./VariantSetupModal', () => ({ __esModule: true, default: () => null }));

const makeValidationError = (errors = []) => {
  const err = new Error('Validation error');
  err.name = 'ValidationError';
  err.inner = errors;
  err.errors = errors.map((e) => e.message);
  return err;
};

// Mock antd components to simplify rendering
const messageMock = { success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() };
vi.mock('antd', async (importOriginal) => {
  const actual = await importOriginal();

  const Form = ({ children, onFinish, ...props }) => (
    <form
      role="form"
      onSubmit={(e) => {
        e.preventDefault();
        onFinish && onFinish();
      }}
      {...props}
    >
      {children}
    </form>
  );
  Form.Item = ({ label, children, help }) => {
    const controlId = label ? `fi-${label.replace(/\s+/g, '-').toLowerCase()}` : undefined;
    const injectLabel = (child) =>
      React.isValidElement(child)
        ? React.cloneElement(child, {
            id: child.props.id || controlId,
            'aria-label': label || child.props['aria-label'],
          })
        : child;
    const processed = Array.isArray(children) ? children.map(injectLabel) : injectLabel(children);

    return (
      <div>
        {label ? <label htmlFor={controlId}>{label}</label> : null}
        {processed}
        {help ? <div role="alert">{help}</div> : null}
      </div>
    );
  };

  const Input = ({ onChange, ...props }) => (
    <input {...props} onChange={(e) => onChange && onChange(e)} />
  );
  Input.TextArea = ({ onChange, ...props }) => (
    <textarea {...props} onChange={(e) => onChange && onChange(e)} />
  );
  const InputNumber = ({ onChange, formatter, parser, ...props }) => (
    <input
      type="number"
      {...props}
      onChange={(e) => {
        const val = e.target.value === '' ? '' : Number(e.target.value);
        onChange && onChange(val);
      }}
    />
  );
  const Select = ({ children, options = [], mode, onChange, value, id, 'aria-label': ariaLabel }) => (
    <select
      id={id}
      aria-label={ariaLabel}
      multiple={mode === 'multiple'}
      value={
        mode === 'multiple'
          ? (Array.isArray(value) ? value.map(String) : [])
          : value != null ? String(value) : ''
      }
      onChange={(e) => {
        const val = mode === 'multiple'
          ? Array.from(e.target.selectedOptions).map((o) => o.value)
          : e.target.value;
        onChange && onChange(val);
      }}
    >
      {children ||
        options.map((opt) => (
          <option key={opt.value} value={opt.value}>
            {opt.label}
          </option>
        ))}
    </select>
  );
  const Checkbox = ({ children, ...props }) => (
    <label>
      <input type="checkbox" {...props} />
      {children}
    </label>
  );
  const Button = ({ children, onClick, htmlType, type, block, loading, ...props }) => (
    <button type={htmlType || 'button'} onClick={onClick} {...props}>
      {children}
    </button>
  );
  const Spin = ({ children }) => <div>{children}</div>;
  const Space = ({ children }) => <div>{children}</div>;
  const Row = ({ children }) => <div>{children}</div>;
  const Col = ({ children }) => <div>{children}</div>;

  const MockTreeSelect = ({ onChange = () => {}, value = [], treeData = [], id, 'aria-label': ariaLabel }) => {
    const normalizedValue = Array.isArray(value) ? value : value ? [value] : [];
    const options =
      Array.isArray(treeData) && treeData.length > 0
        ? treeData
        : [
            { value: 1, title: 'Category 1' },
            { value: 2, title: 'Category 2' },
          ];

  return (
      <select
        id={id}
        aria-label={ariaLabel}
        data-testid="tree-select"
        multiple
        value={normalizedValue.map((v) => String(v))}
      onChange={(e) => {
        const selected = Array.from(e.target.selectedOptions).map((o) => parseInt(o.value, 10));
        onChange(selected);
      }}
    >
        {options.map((opt) => (
          <option key={opt.value || opt.id} value={opt.value || opt.id}>
            {opt.title || opt.name}
          </option>
        ))}
      </select>
    );
  };
  MockTreeSelect.SHOW_PARENT = 'SHOW_PARENT';

  return {
    ...actual,
    App: {
      useApp: () => ({
        message: messageMock,
      }),
    },
    Form,
    Input,
    InputNumber,
    Select,
    Checkbox,
    Button,
    Spin,
    Space,
    Row,
    Col,
    TreeSelect: MockTreeSelect,
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

    // Mock category fetch thunk to avoid plain-object dispatch errors
    fetchCategoryTree.mockImplementation(() => {
      const payload = [
        { id: 1, name: 'Category 1', children: [] },
        { id: 2, name: 'Category 2', children: [] },
      ];
      const promise = Promise.resolve({ payload, type: 'category/fetchCategoryTree/fulfilled' });
      promise.unwrap = () => Promise.resolve(payload);
      return () => promise;
    });

    // Mock product list refresh thunk
    fetchProducts.mockImplementation(() => {
      const payload = [];
      const promise = Promise.resolve({ payload, type: 'product/fetchProducts/fulfilled' });
      promise.unwrap = () => Promise.resolve(payload);
      return () => promise;
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
      expect(screen.getByLabelText(/^Giá bán$/i)).toBeInTheDocument();
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

      return waitFor(async () => {
        expect(await screen.findByRole('button', { name: /Cập nhật sản phẩm/i })).toBeInTheDocument();
        expect(await screen.findByRole('button', { name: /Thiết lập biến thể/i })).toBeInTheDocument();
      });
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

      const priceInput = screen.getAllByLabelText(/Giá bán$/i)[0];
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
      productSchema.validate.mockRejectedValueOnce(
        makeValidationError([
          { path: 'code', message: 'Mã hàng là bắt buộc' },
          { path: 'name', message: 'Tên sản phẩm là bắt buộc' },
          { path: 'selling_price', message: 'Giá bán phải lớn hơn 0' },
        ])
      );
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
      productSchema.validate.mockRejectedValueOnce(
        makeValidationError([{ path: 'selling_price', message: 'Giá bán phải lớn hơn 0' }])
      );
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getAllByLabelText(/Giá bán$/i)[0];
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '0' } });
      fireEvent.blur(priceInput);

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(productSchema.validate).toHaveBeenCalled();
        expect(productApi.createProduct).not.toHaveBeenCalled();
      });
    });
  });

  describe('Form Submission', () => {
    test('submits create form successfully', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getAllByLabelText(/Giá bán$/i)[0];
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
        expect(messageMock.success).toHaveBeenCalledWith('✅ Tạo mới sản phẩm thành công', 3);
        expect(mockOnSuccess).toHaveBeenCalled();
      });
    });

    test('submits edit form successfully', async () => {
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
      const form = screen.getByRole('form');

      fireEvent.change(nameInput, { target: { value: 'Updated Product' } });
      await act(async () => {
        fireEvent.click(submitButton);
        fireEvent.submit(form);
      });

      await waitFor(() => {
        expect(productApi.updateProduct).toHaveBeenCalledWith(
          1,
          expect.objectContaining({
            name: 'Updated Product',
            code: 'TEST-001',
          })
        );
        expect(messageMock.success).toHaveBeenCalled();
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
      const priceInput = screen.getAllByLabelText(/Giá bán$/i)[0];
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
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="create" onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const codeInput = screen.getByLabelText(/Mã hàng/i);
      const nameInput = screen.getByLabelText(/Tên sản phẩm/i);
      const priceInput = screen.getByLabelText(/^Giá bán$/i);
      const submitButton = screen.getByRole('button', { name: /Thêm sản phẩm/i });

      fireEvent.change(codeInput, { target: { value: 'TEST-001' } });
      fireEvent.change(nameInput, { target: { value: 'Test Product' } });
      fireEvent.change(priceInput, { target: { value: '0' } });

      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(messageMock.error).toHaveBeenCalledWith('Giá bán phải lớn hơn 0');
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

    test('shows variant setup button in edit mode for simple products', async () => {
      render(
        <TestWrapper store={mockStore}>
          <ProductForm mode="edit" productId={1} onSuccess={mockOnSuccess} onCancel={mockOnCancel} />
        </TestWrapper>
      );

      const variantButton = await screen.findByRole('button', { name: /Thiết lập biến thể/i });
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

      const priceInput = screen.getByLabelText(/^Giá bán$/i);
      fireEvent.change(priceInput, { target: { value: '200' } });
      const form = screen.getByRole('form');

      const submitButton = screen.getByRole('button', { name: /Cập nhật sản phẩm/i });
      await act(async () => {
        fireEvent.click(submitButton);
        fireEvent.submit(form);
      });

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
