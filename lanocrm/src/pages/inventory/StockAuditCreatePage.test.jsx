import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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
    expect(await screen.findByText(/Thêm sản phẩm từ file excel/i)).toBeInTheDocument();
    const uploadLabels = screen.getAllByText(/Chọn file dữ liệu/i);
    expect(uploadLabels.length).toBeGreaterThan(0);
  });

  it('loads sample data and updates summary', async () => {
    renderPage();

    fireEvent.click(screen.getByText(/Dùng dữ liệu mẫu/i));

    await waitFor(() => {
      expect(screen.getByText('VDN099-Xanh')).toBeInTheDocument();
    });

    expect(screen.getByText('29')).toBeInTheDocument();
  });

  it('recalculates mismatch count when quantities change', async () => {
    renderPage();

    fireEvent.click(screen.getByText(/Dùng dữ liệu mẫu/i));

    const inputs = await screen.findAllByRole('spinbutton');
    await userEvent.clear(inputs[0]);
    await userEvent.type(inputs[0], '10');

    await waitFor(() => {
      expect(screen.getByText(/Lệch \(1\)/)).toBeInTheDocument();
    });
  });
});
