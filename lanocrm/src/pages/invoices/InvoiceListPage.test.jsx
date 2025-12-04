import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import invoicesReducer from '../../store/slices/invoiceSlice';
import branchReducer from '../../store/slices/branchSlice';
import InvoiceListPage from './InvoiceListPage';
import invoiceApi from '../../api/invoiceApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/invoiceApi', () => ({
  default: {
    getInvoices: vi.fn(),
    getInvoice: vi.fn(),
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
      invoices: invoicesReducer,
      branch: branchReducer,
    },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <InvoiceListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('InvoiceListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders invoice list and allows selecting row', async () => {
    invoiceApi.getInvoices.mockResolvedValue({
      data: [
        {
          id: 1,
          invoice_code: 'HD031576',
          customer_name: 'a Tiến',
          delivery_status: 'delivered',
          invoice_status: 'completed',
          issued_at: '2025-11-26 12:01:00',
          customer_payable: 2150000,
          customer_paid: 2150000,
        },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { customer_payable: 2150000, customer_paid: 2150000, cod_amount: 0, shipping_fee: 0 },
    });
    invoiceApi.getInvoice.mockResolvedValue({
      data: {
        id: 1,
        invoice_code: 'HD031576',
        customer_name: 'a Tiến',
        payments: [
          { id: 1, code: 'TTHD031575', time: '2025-11-25 15:42:00', creator: 'nhung', amount: 1800000, method: 'Chuyển khoản', status: 'Đã thanh toán', cash_flow: 1800000 },
        ],
      },
    });
    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByLabelText('Theo mã hóa đơn')).toBeInTheDocument();
    expect(await screen.findByText('HD031576')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã hóa đơn');
    fireEvent.change(searchInput, { target: { value: 'HD031' } });
    await waitFor(() => {
      expect(invoiceApi.getInvoices).toHaveBeenCalled();
    });

    fireEvent.click(screen.getByText('HD031576'));
    await waitFor(() => expect(invoiceApi.getInvoice).toHaveBeenCalled());
    expect(await screen.findByText('Lịch sử thanh toán')).toBeInTheDocument();
    fireEvent.click(screen.getByText('Lịch sử thanh toán'));
    expect(await screen.findByText('TTHD031575')).toBeInTheDocument();
  }, 10000);
});
