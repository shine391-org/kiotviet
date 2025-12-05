import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import disposalReducer from '../../store/slices/disposalSlice';
import branchReducer from '../../store/slices/branchSlice';
import DisposalListPage from './DisposalListPage';
import disposalApi from '../../api/disposalApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/disposalApi', () => ({
  default: {
    getDisposals: vi.fn(),
    getDisposal: vi.fn(),
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
      disposals: disposalReducer,
      branch: branchReducer,
    },
  });

  return render(
    <Provider store={store}>
      <ConfigProvider>
        <AntdApp>
          <DisposalListPage />
        </AntdApp>
      </ConfigProvider>
    </Provider>
  );
};

describe('DisposalListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders disposal list and loads detail on row click', async () => {
    disposalApi.getDisposals.mockResolvedValue({
      data: [
        {
          id: 1,
          dispose_code: 'XH000047',
          total_value: 540000,
          total_quantity: 1,
          disposed_at: '2024-06-16 12:32:00',
          branch_name: 'Lano - HN',
          creator_name: '01686100999',
          executor_name: '01686100999',
          status: 'completed',
          notes: '',
        },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      totals: { total_value: 540000, total_quantity: 1 },
    });

    disposalApi.getDisposal.mockResolvedValue({
      data: {
        id: 1,
        dispose_code: 'XH000047',
        total_value: 540000,
        total_quantity: 1,
        disposed_at: '2024-06-16 12:32:00',
        created_at: '2024-06-16 12:00:00',
        branch_name: 'Lano - HN',
        creator_name: '01686100999',
        executor_name: '01686100999',
        status: 'completed',
        notes: 'Không có',
        items: [
          { id: 10, sku: 'CLT70', name: 'Túi clutch', quantity: 1, cost_price: 540000, disposal_value: 540000 },
        ],
      },
    });

    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã xuất hủy')).toBeInTheDocument();
    expect(await screen.findByText('XH000047')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã xuất hủy');
    fireEvent.change(searchInput, { target: { value: 'XH000' } });
    await waitFor(() => expect(disposalApi.getDisposals).toHaveBeenCalled());

    fireEvent.click(screen.getByText('XH000047'));
    await waitFor(() => expect(disposalApi.getDisposal).toHaveBeenCalled());
    expect(await screen.findByText(/Phiếu xuất hủy XH000047/i)).toBeInTheDocument();
  }, 10000);
});
