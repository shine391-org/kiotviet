import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import { MemoryRouter } from 'react-router-dom';
import stockAuditReducer from '../../store/slices/stockAuditSlice';
import StockAuditListPage from './StockAuditListPage';
import stockAuditApi from '../../api/stockAuditApi';

vi.mock('../../api/stockAuditApi', () => ({
  default: {
    getAudits: vi.fn(),
    getAudit: vi.fn(),
  },
}));

const renderPage = () => {
  const store = configureStore({
    reducer: {
      stockAudits: stockAuditReducer,
    },
  });

  return render(
    <MemoryRouter>
      <Provider store={store}>
        <ConfigProvider>
          <AntdApp>
            <StockAuditListPage />
          </AntdApp>
        </ConfigProvider>
      </Provider>
    </MemoryRouter>
  );
};

describe('StockAuditListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders list and calls API', async () => {
    stockAuditApi.getAudits.mockResolvedValue({
      data: [
        {
          code: 'SK001',
          createdTime: '2025-04-27T13:23:00Z',
          creatorName: 'Tester',
          status: 'balanced',
        },
      ],
      pagination: { page: 1, limit: 15, total: 1 },
      summary: { totalActualQuantity: 150 },
    });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã phiếu kiểm')).toBeInTheDocument();
    expect(await screen.findByText('SK001')).toBeInTheDocument();
    expect(stockAuditApi.getAudits).toHaveBeenCalledTimes(1);
  });

  it('debounces search and triggers fetch', async () => {
    stockAuditApi.getAudits.mockResolvedValue({ data: [], pagination: { page: 1, limit: 15, total: 0 }, summary: {} });

    renderPage();

    const searchInput = await screen.findByPlaceholderText('Theo mã phiếu kiểm');
    fireEvent.change(searchInput, { target: { value: 'SK0' } });

    await waitFor(
      () => {
        expect(stockAuditApi.getAudits).toHaveBeenCalledTimes(2);
      },
      { timeout: 2500 }
    );
  });

  it('allows toggling columns visibility', async () => {
    stockAuditApi.getAudits.mockResolvedValue({
      data: [
        {
          code: 'SK010',
          createdTime: '2025-04-18T09:05:00Z',
          creatorName: 'Tester',
          notes: 'Note',
          status: 'draft',
        },
      ],
      pagination: { page: 1, limit: 15, total: 1 },
      summary: {},
    });

    renderPage();

    await screen.findByText('SK010');

    const toggleButtons = screen.getAllByText('Ẩn hiện cột');
    fireEvent.click(toggleButtons[0]);

    const noteCheckbox = screen.getByRole('checkbox', { name: 'Ghi chú' });
    fireEvent.click(noteCheckbox);

    const table = screen.getByRole('table');
    expect(within(table).queryByText('Ghi chú')).not.toBeInTheDocument();
  });
});
