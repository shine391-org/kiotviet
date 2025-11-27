import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import shipmentReducer from '../../store/slices/shipmentSlice';
import branchReducer from '../../store/slices/branchSlice';
import ShipmentListPage from './ShipmentListPage';
import shipmentApi from '../../api/shipmentApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/shipmentApi', () => ({
  default: {
    getShipments: vi.fn(),
    getShipment: vi.fn(),
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
      shipments: shipmentReducer,
      branch: branchReducer,
    },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <ShipmentListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('ShipmentListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders list, allows search and loads detail', async () => {
    shipmentApi.getShipments.mockResolvedValue({
      data: [
        {
          id: 1,
          code: 'SHP-1',
          invoice_code: 'INV-1',
          customer_name: 'Khách lẻ',
          delivery_status: 'pending',
          delivery_partner_name: 'GHN',
          created_at: '2025-11-26T09:00:00Z',
          delivery_time: null,
          cod_amount: 150000,
        },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      summary: { cod_total: 150000 },
    });
    shipmentApi.getShipment.mockResolvedValue({
      data: { id: 1, code: 'SHP-1', invoice_code: 'INV-1', delivery_status: 'pending', delivery_history: [] },
    });
    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã vận đơn')).toBeInTheDocument();
    expect(await screen.findByText('SHP-1')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã vận đơn');
    fireEvent.change(searchInput, { target: { value: 'SHP' } });

    await waitFor(() => {
      expect(shipmentApi.getShipments).toHaveBeenCalledTimes(2);
    }, { timeout: 2500 });

    fireEvent.click(screen.getByText('SHP-1'));

    await waitFor(() => {
      expect(shipmentApi.getShipment).toHaveBeenCalledWith(1);
    });
  }, 10000);
});
