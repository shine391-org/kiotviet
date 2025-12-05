import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import returnsReducer from '../../store/slices/returnSlice';
import branchReducer from '../../store/slices/branchSlice';
import ReturnListPage from './ReturnListPage';
import returnApi from '../../api/returnApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/returnApi', () => ({
  default: {
    getReturns: vi.fn(),
    getReturn: vi.fn(),
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
      returns: returnsReducer,
      branch: branchReducer,
    },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <ReturnListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('ReturnListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders returns list and loads detail on row click', async () => {
    returnApi.getReturns.mockResolvedValue({
      data: [
        {
          id: 1,
          return_code: 'TH000942',
          invoice_code: 'HD031535',
          shipping_code: 'GY6XNTTM',
          seller_name: 'Chị Phương Anh',
          return_time: '2025-11-25 14:55:00',
          created_at: '2025-11-25 14:55:00',
          customer_name: 'Nguyễn Quang Trung',
          branch_name: 'Lano - HN',
          status: 'completed',
          goods_amount: 1050000,
          need_refund: 1050000,
          refunded_amount: 0,
        },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { goods_total: 1050000, need_refund: 1050000, refunded: 0 },
    });
    returnApi.getReturn.mockResolvedValue({
      data: {
        id: 1,
        return_code: 'TH000942',
        invoice_code: 'HD031535',
        shipping_code: 'GY6XNTTM',
        seller_name: 'Chị Phương Anh',
        receiver_name: 'Chị Phương Anh',
        creator_name: 'Chị Phương Anh',
        return_time: '2025-11-25 14:55:00',
        created_at: '2025-11-25 14:55:00',
        customer_name: 'Nguyễn Quang Trung',
        branch_name: 'Lano - HN',
        channel: 'Bán trực tiếp',
        status: 'completed',
        goods_amount: 1050000,
        discount_amount: 0,
        return_fee: 0,
        need_refund: 1050000,
        refunded_amount: 0,
        items: [
          { id: 10, sku: 'KT77', name: 'Túi đeo chéo', quantity: 1, return_price: 1050000, discount: 0, restock_price: 1050000 },
        ],
      },
    });
    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã phiếu trả')).toBeInTheDocument();
    expect(await screen.findByText('TH000942')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã phiếu trả');
    fireEvent.change(searchInput, { target: { value: 'TH000' } });
    await waitFor(() => expect(returnApi.getReturns).toHaveBeenCalled());

    fireEvent.click(screen.getByText('TH000942'));
    await waitFor(() => expect(returnApi.getReturn).toHaveBeenCalled());
    expect(await screen.findByText(/Phiếu trả TH000942/i)).toBeInTheDocument();
  }, 10000);
});
