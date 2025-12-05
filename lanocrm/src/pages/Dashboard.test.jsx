import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { ConfigProvider } from 'antd';
import { vi, beforeEach, describe, expect, it } from 'vitest';
import Dashboard from './Dashboard';
import dashboardReducer from '../store/slices/dashboardSlice';

// Mock Chart.js wrapper to avoid canvas in JSDOM
vi.mock('react-chartjs-2', () => ({
  Bar: () => <div data-testid="chart-mock" />,
}));

// Hoisted API mocks (avoid ReferenceError when mocked module loads first)
const mockApi = vi.hoisted(() => ({
  getKpiToday: vi.fn(),
  getRevenueChart: vi.fn(),
  getTopProducts: vi.fn(),
  getTopCustomers: vi.fn(),
  getActivities: vi.fn(),
}));

vi.mock('../api/dashboardApi', () => mockApi);

const renderDashboard = () => {
  const store = configureStore({
    reducer: {
      dashboard: dashboardReducer,
      auth: () => ({ user: { username: 'Tester' }, isAuthenticated: true }),
    },
  });

  render(
    <Provider store={store}>
      <ConfigProvider>
        <Dashboard />
      </ConfigProvider>
    </Provider>
  );

  return store;
};

describe('Dashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockApi.getKpiToday.mockResolvedValue({
      data: {
        revenue: 1_200_000,
        returns: 200_000,
        netRevenue: 1_000_000,
        netChange: -12.5,
        comparisonLabel: 'so với cùng kỳ tháng trước',
      },
    });

    mockApi.getRevenueChart.mockResolvedValue({
      data: {
        labels: ['01', '02'],
        values: [1_000_000, 2_000_000],
        branchLabel: 'Lano - HN',
        total: 3_000_000,
      },
    });

    mockApi.getTopProducts.mockResolvedValue({
      data: [{ id: 1, name: 'Áo thun cổ tròn', value: 8_000_000 }],
    });

    mockApi.getTopCustomers.mockResolvedValue({
      data: [{ id: 1, name: 'Khách VIP', value: 6_000_000 }],
    });

    mockApi.getActivities.mockResolvedValue({
      data: [
        {
          id: 'act-1',
          type: 'invoice',
          username: 'tester',
          action: 'Bán đơn hàng',
          amount: 500_000,
          timeAgo: '2 giờ trước',
          linkedPage: '#/invoices/1',
        },
      ],
    });
  });

  it('renders header and KPI cards after data load', async () => {
    renderDashboard();

    expect(screen.getByText(/Tổng quan/i)).toBeInTheDocument();
    expect(screen.getByText(/Xin chào, Tester/i)).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText(/Kết quả bán hàng hôm nay/i)).toBeInTheDocument();
      expect(screen.getByText(/Doanh thu thuần/)).toBeInTheDocument();
    });

    expect(await screen.findByText('1,200,000 đ')).toBeInTheDocument();
    expect(screen.getByText('1,000,000 đ')).toBeInTheDocument();
    expect(screen.getByText('-12.50%')).toBeInTheDocument();
  });

  it('renders chart and rankings', async () => {
    renderDashboard();

    expect(await screen.findByTestId('chart-mock')).toBeInTheDocument();
    expect(await screen.findByText('Áo thun cổ tròn')).toBeInTheDocument();
    const rows = document.querySelectorAll('.ranking-row');
    expect(rows.length).toBeGreaterThan(0);
    const firstValue = rows[0]?.querySelector('.ranking-value')?.textContent || '';
    expect(firstValue).toMatch(/8/);
    expect(screen.getByText('Khách VIP')).toBeInTheDocument();
  });

  it('changes revenue breakdown to hourly and calls API with correct params', async () => {
    renderDashboard();

    const hourlyTab = await screen.findByText('Theo giờ');
    await userEvent.click(hourlyTab);

    await waitFor(() => {
      expect(mockApi.getRevenueChart).toHaveBeenCalledWith(
        expect.objectContaining({ period: 'hour' })
      );
    });
  });

  it('shows activity list items', async () => {
    renderDashboard();

    expect(await screen.findByText('tester')).toBeInTheDocument();
    expect(screen.getByText('Bán đơn hàng')).toBeInTheDocument();
    expect(screen.getByText('500,000 đ')).toBeInTheDocument();
    expect(screen.getByText(/2 giờ trước/)).toBeInTheDocument();
  });
});
