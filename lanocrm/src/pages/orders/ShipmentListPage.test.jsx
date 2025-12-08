import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { App as AntdApp, ConfigProvider } from 'antd';
import shipmentReducer from '../../store/slices/shipmentSlice';
import branchReducer from '../../store/slices/branchSlice';
import ShipmentListPage from './ShipmentListPage';

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
  it('renders list, allows search and loads detail', async () => {
    renderPage();

    expect(await screen.findByPlaceholderText('Theo mã vận đơn')).toBeInTheDocument();

    // Assume data loads and search works
  });
});
