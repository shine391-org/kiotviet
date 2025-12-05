import React from 'react';
import { describe, it, vi, expect } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import ordersReducer from '../../store/slices/orderSlice';
import branchReducer from '../../store/slices/branchSlice';
import OrderListPage from './OrderListPage';
import orderApi from '../../api/orderApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/orderApi', () => ({
  default: {
    getOrders: vi.fn(),
    getOrder: vi.fn(),
  },
}));

vi.mock('../../api/branchApi', () => ({
  default: {
    getBranches: vi.fn(),
    createBranch: vi.fn(),
    updateBranch: vi.fn(),
    deleteBranch: vi.fn(),
    setDefaultBranch: vi.fn(),
  },
}));

const renderPage = () => {
  const store = configureStore({
    reducer: {
      orders: ordersReducer,
      branch: branchReducer,
    },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <OrderListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('OrderListPage', () => {
  it('renders list and allows search', async () => {
    orderApi.getOrders.mockResolvedValue({
      data: [
        { id: 1, order_number: 'ORD-1', order_date: '2025-11-19 08:07:00', customer_name: 'Khách lẻ', total: 990000, paid_amount: 0, debt_amount: 990000, status: 'draft' },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { total_amount: 990000, paid_amount: 0, debt_amount: 990000 },
    });
    orderApi.getOrder.mockResolvedValue({ success: true, data: { id: 1, order_number: 'ORD-1', items: [] } });
    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã phiếu đặt')).toBeInTheDocument();
    expect(await screen.findByText('ORD-1')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã phiếu đặt');
    fireEvent.change(searchInput, { target: { value: 'ORD' } });

    await waitFor(() => {
      expect(orderApi.getOrders).toHaveBeenCalled();
    });
  }, 10000);
});
