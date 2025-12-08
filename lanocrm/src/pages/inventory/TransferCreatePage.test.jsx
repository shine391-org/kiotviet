import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import branchReducer from '../../store/slices/branchSlice';
import authReducer from '../../store/slices/authSlice';
import TransferCreatePage from './TransferCreatePage';

vi.mock('../../api/branchApi', () => ({
  default: {
    getBranches: vi.fn().mockResolvedValue({ data: [{ id: 1, name: 'Chi nhánh HN' }, { id: 2, name: 'Chi nhánh HCM' }] }),
  },
}));

vi.mock('../../api/productApi', () => ({
  getProducts: vi.fn().mockResolvedValue({ success: true, data: [] }),
}));

vi.mock('../../api/transferApi', () => ({
  default: {
    createTransfer: vi.fn().mockResolvedValue({ success: true, data: { id: 1 } }),
    submitTransfer: vi.fn().mockResolvedValue({ success: true }),
  },
}));

const renderPage = () => {
  const store = configureStore({
    reducer: {
      branch: branchReducer,
      auth: authReducer,
    },
    preloadedState: {
      branch: { branches: [{ id: 1, name: 'Chi nhánh HN' }, { id: 2, name: 'Chi nhánh HCM' }], loading: false },
      auth: { user: { id: 1, name: 'Test User', branch_id: 1 }, isAuthenticated: true },
    },
  });

  return render(
    <MemoryRouter>
      <Provider store={store}>
        <ConfigProvider>
          <AntdApp>
            <TransferCreatePage />
          </AntdApp>
        </ConfigProvider>
      </Provider>
    </MemoryRouter>
  );
};

describe('TransferCreatePage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders empty state and actions', () => {
    renderPage();

    expect(screen.getByText('Tạo phiếu chuyển hàng')).toBeInTheDocument();
    expect(screen.getByText('Chưa có sản phẩm nào')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Lưu tạm/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Hoàn thành/i })).toBeInTheDocument();
  });

  it('back button navigates to list', () => {
    renderPage();
    const buttons = screen.getAllByRole('button');
    const backBtn = buttons[0]; // First button is the back button
    expect(backBtn).toBeInTheDocument();
    fireEvent.click(backBtn);
  });
});
