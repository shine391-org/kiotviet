import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, waitFor, fireEvent, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import { MemoryRouter } from 'react-router-dom';
import stockAuditReducer from '../../store/slices/stockAuditSlice';
import StockAuditListPage from './StockAuditListPage';

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
  it('renders list', async () => {
    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã phiếu kiểm')).toBeInTheDocument();
  });

  it('allows search input', async () => {
    renderPage();

    const searchInput = await screen.findByPlaceholderText('Theo mã phiếu kiểm');
    fireEvent.change(searchInput, { target: { value: 'SK0' } });

    expect(searchInput.value).toBe('SK0');
  });

  it('allows toggling columns visibility', async () => {
    renderPage();

    const toggleButtons = screen.getAllByText('Ẩn hiện cột');
    expect(toggleButtons.length).toBeGreaterThan(0);
  });
});
