/**
 * Integration Tests for CustomerListPage
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
import { describe, it, expect, beforeAll } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { App as AntdApp, ConfigProvider } from 'antd';
import { configureStore } from '@reduxjs/toolkit';
import CustomerListPage from './CustomerListPage';
import customerReducer from '../../store/slices/customerSlice';

// NO MOCKING - Real API calls only

const createStore = () =>
  configureStore({
    reducer: { customer: customerReducer },
  });

const renderPage = () =>
  render(
    <Provider store={createStore()}>
      <BrowserRouter>
        <ConfigProvider>
          <AntdApp>
            <CustomerListPage />
          </AntdApp>
        </ConfigProvider>
      </BrowserRouter>
    </Provider>
  );

describe('CustomerListPage', () => {
  describe('Page Structure', () => {
    it('renders search input', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByPlaceholderText(/mã, tên, số điện thoại/i)).toBeInTheDocument();
      });
    });

    it('renders add customer button', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Khách hàng/i })).toBeInTheDocument();
      });
    });

    it('renders filter panel', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByText('Bộ lọc')).toBeInTheDocument();
        expect(screen.getByText('Loại khách hàng')).toBeInTheDocument();
        expect(screen.getByText('Giới tính')).toBeInTheDocument();
        expect(screen.getByText('Trạng thái')).toBeInTheDocument();
      });
    });

    it('renders detail panel', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByText('Chi tiết khách hàng')).toBeInTheDocument();
      });
    });

    it('renders action buttons', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Export/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Làm mới/i })).toBeInTheDocument();
      });
    });
  });

  describe('Create Customer Flow', () => {
    it('opens create drawer when clicking add button', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Khách hàng/i })).toBeInTheDocument();
      });

      fireEvent.click(screen.getByRole('button', { name: /Khách hàng/i }));

      await waitFor(() => {
        expect(screen.getByText('Tạo khách hàng')).toBeInTheDocument();
      });
    });

    it('shows required form fields in create drawer', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Khách hàng/i })).toBeInTheDocument();
      });

      fireEvent.click(screen.getByRole('button', { name: /Khách hàng/i }));

      await waitFor(() => {
        expect(screen.getByLabelText(/Tên khách hàng/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Điện thoại 1/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/Email/i)).toBeInTheDocument();
      });
    });

    it('closes drawer when clicking cancel', async () => {
      renderPage();
      
      await waitFor(() => {
        expect(screen.getByRole('button', { name: /Khách hàng/i })).toBeInTheDocument();
      });

      fireEvent.click(screen.getByRole('button', { name: /Khách hàng/i }));

      await waitFor(() => {
        expect(screen.getByText('Tạo khách hàng')).toBeInTheDocument();
      });

      fireEvent.click(screen.getByRole('button', { name: /Bỏ qua/i }));

      await waitFor(() => {
        expect(screen.queryByText('Tạo khách hàng')).not.toBeInTheDocument();
      });
    });
  });

  describe('Data Loading (requires backend)', () => {
    it('displays customer table', async () => {
      renderPage();
      
      // Table should render - data depends on backend
      await waitFor(
        () => {
          expect(screen.getByRole('table')).toBeInTheDocument();
        },
        { timeout: 10000 }
      );
    });

    it('renders table while fetching', async () => {
      renderPage();
      
      // Should show table (loading or with data)
      await waitFor(() => {
        expect(screen.getByRole('table')).toBeInTheDocument();
      });
    });
  });
});
