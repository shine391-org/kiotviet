import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import BranchPage from './BranchPage';
import branchReducer from '../store/slices/branchSlice';
import * as branchSlice from '../store/slices/branchSlice';

// Mock dependencies
vi.mock('../store/slices/branchSlice', async () => {
    const actual = await vi.importActual('../store/slices/branchSlice');
    return {
        ...actual,
        fetchBranches: vi.fn(() => () => {}), // Return a no-op function
        deleteBranch: vi.fn(() => () => {}),
        setDefaultBranch: vi.fn(() => () => {}),
    };
});

vi.mock('../components/Branch/BranchForm', () => ({
    default: ({ branch, onClose, onSuccess }) => (
        <div>
            <h2>{branch ? 'Chỉnh sửa chi nhánh' : 'Thêm mới chi nhánh'}</h2>
            <button onClick={onClose}>Close</button>
            <button onClick={onSuccess}>Success</button>
        </div>
    )
}));


describe('BranchPage Component', () => {
  let store;

  beforeEach(() => {
    const preloadedState = {
      branch: {
        branches: [
          { id: 1, name: 'Main Branch', code: 'MB', address: '123 Main St', phone: '123-456-7890', status: 'active', is_default: 1 },
          { id: 2, name: 'Second Branch', code: 'SB', address: '456 Second St', phone: '098-765-4321', status: 'inactive', is_default: 0 },
        ],
        loading: false,
        error: null,
      },
    };

    store = configureStore({
      reducer: {
        branch: branchReducer,
      },
      preloadedState,
    });

    window.confirm = vi.fn(() => true);
    window.alert = vi.fn();
    vi.clearAllMocks();
  });

  it('renders the page title and add new button', () => {
    render(
      <Provider store={store}>
        <BranchPage />
      </Provider>
    );

    expect(screen.getByText('🏢 Quản lý Chi nhánh')).toBeInTheDocument();
    expect(screen.getByText('Thêm chi nhánh')).toBeInTheDocument();
  });

  it('displays a list of branches', () => {
    render(
      <Provider store={store}>
        <BranchPage />
      </Provider>
    );

    expect(screen.getAllByText('Main Branch')[0]).toBeInTheDocument();
    expect(screen.getAllByText('Second Branch')[0]).toBeInTheDocument();
  });

  it('filters branches based on search term', () => {
    render(
      <Provider store={store}>
        <BranchPage />
      </Provider>
    );

    const searchInput = screen.getByPlaceholderText('Tìm kiếm theo tên, mã, địa chỉ...');
    fireEvent.change(searchInput, { target: { value: 'Main' } });

    expect(screen.getAllByText('Main Branch')[0]).toBeInTheDocument();
    expect(screen.queryByText('Second Branch')).not.toBeInTheDocument();
  });

  it('shows the branch form when the "Add New" button is clicked', async () => {
    render(
      <Provider store={store}>
        <BranchPage />
      </Provider>
    );

    fireEvent.click(screen.getByText('Thêm chi nhánh'));

    await waitFor(() => {
        expect(screen.getByText('Thêm mới chi nhánh')).toBeInTheDocument();
    });
  });
});
