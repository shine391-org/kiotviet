/**
 * ProductPriceListsTab Component Tests
 * @file src/components/products/ProductPriceListsTab.test.jsx
 * @description Unit tests for ProductPriceListsTab component
 * @agent-test: ProductPriceListsTab - Component testing
 * @agent-pattern: React Testing Library + Vitest
 */

import { describe, it, expect, beforeEach, beforeAll, afterAll, vi } from 'vitest';
import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';
import { ConfigProvider } from 'antd';
import ProductPriceListsTab from './ProductPriceListsTab';

// Mock the APIs
vi.mock('../../api/priceListApi', () => ({
  getPriceLists: vi.fn(),
}));

vi.mock('../../api/productApi', () => ({
  getProductDetail: vi.fn(),
}));

// Mock Ant Design message
vi.mock('antd/message', () => ({
  error: vi.fn(),
}));

const { getPriceLists } = await import('../../api/priceListApi');
const { getProductDetail } = await import('../../api/productApi');

describe('ProductPriceListsTab', () => {
  const mockProductId = 123;
  const originalSetProperty = CSSStyleDeclaration.prototype.setProperty;

  // JSDOM + AntD inject CSS vars that cssstyle cannot parse fully; swallow harmless errors
  beforeAll(() => {
    CSSStyleDeclaration.prototype.setProperty = function(name, value, priority) {
      try {
        return originalSetProperty.call(this, name, value, priority);
      } catch (e) {
        return undefined;
      }
    };
  });

  afterAll(() => {
    CSSStyleDeclaration.prototype.setProperty = originalSetProperty;
  });

  const mockPriceLists = [
    {
      id: 1,
      name: 'Bảng giá VIP',
      status: 'active',
      start_date: '2024-01-01',
      end_date: '2024-12-31',
      customer_groups: ['VIP'],
    },
    {
      id: 2,
      name: 'Bảng giá Retail',
      status: 'active',
      start_date: '2024-01-01',
      end_date: null,
      customer_groups: [],
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    
    // Setup default API responses
    getPriceLists.mockResolvedValue({
      success: true,
      data: mockPriceLists,
    });

    getProductDetail.mockImplementation((_, config) => {
      const priceListId = parseInt(config?.params?.price_list_id, 10) || 0;
      
      if (priceListId === 1) {
        return Promise.resolve({
          success: true,
          data: {
            base_price: 1000000,
            final_price: 900000,
            applied_price: 900000,
          },
        });
      } else if (priceListId === 2) {
        return Promise.resolve({
          success: true,
          data: {
            selling_price: 1000000,
          },
        });
      } else {
        return Promise.reject(new Error('Product not found in price list'));
      }
    });
  });

  const renderComponent = (productId = mockProductId) => {
    return render(
      <ConfigProvider>
        <ProductPriceListsTab productId={productId} />
      </ConfigProvider>
    );
  };

  it('renders component with correct title', () => {
    renderComponent();
    
    expect(screen.getByText('Bảng giá sản phẩm')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /làm mới/i })).toBeInTheDocument();
  });

  it('loads price lists and product prices on mount', async () => {
    renderComponent();

    await waitFor(() => {
      expect(getPriceLists).toHaveBeenCalledTimes(1);
      expect(getProductDetail).toHaveBeenCalledTimes(2);
    });
  });

  it('displays price data correctly in table', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('Bảng giá VIP')).toBeInTheDocument();
      expect(screen.getByText('Bảng giá Retail')).toBeInTheDocument();
    });

    // Check price formatting
    expect(screen.getAllByText('1.000.000 ₫').length).toBeGreaterThan(0);
    expect(screen.getAllByText('900.000 ₫').length).toBeGreaterThan(0);
    
    // Check discount calculation
    expect(screen.getAllByText('-10.0%').length).toBeGreaterThan(0);
  });

  it('displays correct table columns', async () => {
    renderComponent();

    await waitFor(() => {
      expect(screen.getAllByText('Tên bảng giá').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Giá gốc').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Giá bán').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Giảm giá').length).toBeGreaterThan(0);
      expect(screen.getAllByText('Trạng thái').length).toBeGreaterThan(0);
    });
  });

  it('refresh button reloads data', async () => {
    renderComponent();

    await waitFor(() => {
      expect(getPriceLists).toHaveBeenCalledTimes(1);
    });

    const refreshButton = screen.getByRole('button', { name: /làm mới/i });
    fireEvent.click(refreshButton);

    await waitFor(() => {
      expect(getPriceLists).toHaveBeenCalledTimes(2);
    });
  });

  it('displays empty state when no price lists', async () => {
    getPriceLists.mockResolvedValue({
      success: true,
      data: [],
    });

    renderComponent();

    await waitFor(() => {
      expect(screen.getByText('Chưa có dữ liệu bảng giá')).toBeInTheDocument();
    });
  });
});
