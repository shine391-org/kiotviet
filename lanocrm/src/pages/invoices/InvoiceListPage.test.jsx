/**
 * Integration Tests for InvoiceListPage
 * 
 * Per TESTING-RULES.md:
 * - Tests use REAL API calls (no mocking)
 * - Tests run against lanocrm_test database
 * - If tests fail, fix implementation, not the test
 * 
 * Prerequisites:
 * - Backend running with test database
 * - Valid auth token in localStorage or test auth setup
 */

import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { App as AntdApp, ConfigProvider } from 'antd';
import { configureStore } from '@reduxjs/toolkit';
import InvoiceListPage from './InvoiceListPage';
import invoicesReducer from '../../store/slices/invoiceSlice';
import branchReducer from '../../store/slices/branchSlice';

// NO MOCKING - Real API calls only

const createStore = () =>
  configureStore({
    reducer: {
      invoices: invoicesReducer,
      branch: branchReducer,
    },
  });

const renderPage = () =>
  render(
    <Provider store={createStore()}>
      <BrowserRouter>
        <ConfigProvider>
          <AntdApp>
            <InvoiceListPage />
          </AntdApp>
        </ConfigProvider>
      </BrowserRouter>
    </Provider>
  );

describe('InvoiceListPage', () => {
  describe('Page Structure', () => {
    it('renders search input', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByPlaceholderText(/mã hóa đơn/i)).toBeInTheDocument();
      });
    });

    it('renders create button', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Tạo mới/i })).toBeInTheDocument();
      });
    });

    it('renders export button', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Xuất file/i })).toBeInTheDocument();
      });
    });

    it('renders page totals section', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByText(/Khách cần trả:/)).toBeInTheDocument();
        expect(screen.getByText(/Khách đã trả:/)).toBeInTheDocument();
        expect(screen.getByText(/COD:/)).toBeInTheDocument();
      });
    });
  });

  describe('Table Display', () => {
    it('renders invoice table', async () => {
      renderPage();
      
      await waitFor(
        () => {
          expect(screen.getByRole('table')).toBeInTheDocument();
        },
        { timeout: 10000 }
      );
    });

    it('has column visibility toggle', async () => {
      renderPage();
      
      await waitFor(() => {
        const buttons = screen.getAllByRole('button');
        const hasColumnButton = buttons.some(
          (btn) => btn.querySelector('.anticon-column-height')
        );
        expect(hasColumnButton).toBe(true);
      });
    });

    it('has refresh button', async () => {
      renderPage();
      
      await waitFor(() => {
        const buttons = screen.getAllByRole('button');
        const hasRefreshButton = buttons.some(
          (btn) => btn.querySelector('.anticon-reload')
        );
        expect(hasRefreshButton).toBe(true);
      });
    });
  });

  describe('Search Functionality', () => {
    it('allows typing in search input', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByPlaceholderText(/mã hóa đơn/i)).toBeInTheDocument();
      });

      const searchInput = screen.getByPlaceholderText(/mã hóa đơn/i);
      fireEvent.change(searchInput, { target: { value: 'HD001' } });
      
      expect(searchInput).toHaveValue('HD001');
    });
  });

  describe('Detail Panel', () => {
    it('shows empty detail message when no invoice selected', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByText(/Chọn một hóa đơn/i)).toBeInTheDocument();
      });
    });
  });
});
