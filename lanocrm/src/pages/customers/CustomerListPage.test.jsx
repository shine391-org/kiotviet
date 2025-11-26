import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import CustomerListPage from './CustomerListPage';
import customerReducer from '../../store/slices/customerSlice';

const mockList = {
  data: [
    { id: 1, name: 'Anh Tien', customer_type: 'INDIVIDUAL', phone: '0904009288', gender: 'MALE' },
    { id: 2, name: 'Công ty Sao Mai', customer_type: 'COMPANY', phone: '0988776655', gender: 'FEMALE' },
  ],
  pagination: { page: 1, limit: 15, total: 2, total_pages: 1 },
};

const mockDetail = { data: mockList.data[0] };

vi.mock('../../api/customerApi', () => ({
  default: {
    getCustomers: vi.fn(async () => mockList),
    getCustomer: vi.fn(async () => mockDetail),
    createCustomer: vi.fn(async (body) => ({ data: { id: 3, ...body } })),
    updateCustomer: vi.fn(),
  },
}));

import customerApi from '../../api/customerApi';

const renderWithStore = () => {
  const store = configureStore({
    reducer: { customer: customerReducer },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <CustomerListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('CustomerListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders customer table and detail from API data', async () => {
    renderWithStore();

    await waitFor(() => expect(customerApi.getCustomers).toHaveBeenCalledTimes(1));
    const nameCells = await screen.findAllByText('Anh Tien', { exact: false });
    expect(nameCells.length).toBeGreaterThan(0);

    // Detail card auto-loads first customer
    await waitFor(() => expect(screen.getByText(/KH#1/)).toBeInTheDocument());
    const detailCard = screen.getByText('Chi tiết khách hàng').closest('.ant-card');
    expect(detailCard).not.toBeNull();
    if (detailCard) {
      const detailContent = within(detailCard);
      expect(detailContent.getAllByText(/Cá nhân/).length).toBeGreaterThan(0);
    }
  });

  it('opens create modal when clicking add button', async () => {
    renderWithStore();

    await userEvent.click(await screen.findByRole('button', { name: /Khách hàng/i }));
    expect(await screen.findByText('Thêm khách hàng')).toBeInTheDocument();
  });
});
