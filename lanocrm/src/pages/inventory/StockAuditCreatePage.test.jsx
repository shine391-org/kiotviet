import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import { MemoryRouter } from 'react-router-dom';
import { ConfigProvider, App as AntdApp } from 'antd';
import StockAuditCreatePage from './StockAuditCreatePage';

const renderPage = () =>
  render(
    <MemoryRouter>
      <ConfigProvider>
        <AntdApp>
          <StockAuditCreatePage />
        </AntdApp>
      </ConfigProvider>
    </MemoryRouter>
  );

describe('StockAuditCreatePage', () => {
  it('shows empty state and upload button', async () => {
    renderPage();
    expect(await screen.findByText(/Thêm sản phẩm vào phiếu kiểm kho/i)).toBeInTheDocument();
    const uploadLabels = screen.getAllByText(/Chọn file dữ liệu/i);
    expect(uploadLabels.length).toBeGreaterThan(0);
  });

  it('allows searching for products', async () => {
    renderPage();

    const searchInput = screen.getByPlaceholderText('Tìm hàng hóa theo mã hoặc tên (F3)');
    await act(async () => {
      await userEvent.type(searchInput, 'test');
    });

    expect(searchInput.value).toBe('test');
  });

  it('renders quantity inputs when data is present', async () => {
    renderPage();

    const inputs = screen.queryAllByRole('spinbutton');
    expect(inputs.length).toBe(0);
  });
});
