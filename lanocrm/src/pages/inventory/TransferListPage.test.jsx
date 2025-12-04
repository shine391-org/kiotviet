import React from 'react';
import { describe, it, vi, expect, beforeEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import { MemoryRouter } from 'react-router-dom';
import transferReducer from '../../store/slices/transferSlice';
import branchReducer from '../../store/slices/branchSlice';
import TransferListPage from './TransferListPage';
import transferApi from '../../api/transferApi';
import branchApi from '../../api/branchApi';

vi.mock('../../api/transferApi', () => ({
  default: {
    getTransfers: vi.fn(),
    getTransfer: vi.fn(),
    duplicateTransfer: vi.fn(),
    openTransfer: vi.fn(),
    saveReceivingNotes: vi.fn(),
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
      transfers: transferReducer,
      branch: branchReducer,
    },
  });

  return render(
    <MemoryRouter>
      <Provider store={store}>
        <ConfigProvider>
          <AntdApp>
            <TransferListPage />
          </AntdApp>
        </ConfigProvider>
      </Provider>
    </MemoryRouter>
  );
};

describe('TransferListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders list, handles search, and loads detail', async () => {
    transferApi.getTransfers.mockResolvedValue({
      data: [
        {
          code: 'TRF-1',
          creatorName: 'Tester',
          receiverName: 'Receiver',
          transferDate: '2025-11-27T10:00:00Z',
          receiveDate: null,
          createdAt: '2025-11-27T10:00:00Z',
          fromBranch: 'Lano - HCM',
          toBranch: 'Lano - HN',
          quantitySent: 3,
          valueSent: 1000000,
          quantityReceived: 0,
          valueReceived: 0,
          totalItems: 3,
          status: 'draft',
        },
      ],
      pagination: { page: 1, limit: 15, total: 1, total_pages: 1 },
      summary: { totalQtySent: 3, totalValueSent: 1000000, totalQtyReceived: 0, totalValueReceived: 0, totalItems: 3 },
    });
    transferApi.getTransfer.mockResolvedValue({
      transfer: {
        code: 'TRF-1',
        status: 'draft',
        creatorName: 'Tester',
        fromBranch: 'Lano - HCM',
        toBranch: 'Lano - HN',
        transferDate: '2025-11-27T10:00:00Z',
        items: [],
        summary: { totalItems: 3, totalQtySent: 3, totalValueSent: 1000000 },
      },
    });
    branchApi.getBranches.mockResolvedValue({ data: [{ id: 1, name: 'Lano - HN' }] });

    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã phiếu chuyển')).toBeInTheDocument();
    expect(await screen.findByText('TRF-1')).toBeInTheDocument();

    const searchInput = screen.getByPlaceholderText('Theo mã phiếu chuyển');
    fireEvent.change(searchInput, { target: { value: 'TRF' } });

    await waitFor(
      () => {
        expect(transferApi.getTransfers).toHaveBeenCalledTimes(2);
      },
      { timeout: 2500 }
    );

    fireEvent.click(screen.getByText('TRF-1'));

    await waitFor(() => {
      expect(transferApi.getTransfer).toHaveBeenCalledWith('TRF-1');
    });
  }, 10000);
});
