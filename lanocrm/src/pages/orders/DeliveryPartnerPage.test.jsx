import React from 'react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import deliveryPartnerReducer from '../../store/slices/deliveryPartnerSlice';
import DeliveryPartnerPage from './DeliveryPartnerPage';

const renderPage = () => {
  const store = configureStore({
    reducer: { deliveryPartners: deliveryPartnerReducer },
  });

  return render(
    <Provider store={store}>
      <DeliveryPartnerPage />
    </Provider>
  );
};

describe('DeliveryPartnerPage', () => {
  it('renders header actions and loads data', async () => {
    renderPage();

    expect(screen.getByPlaceholderText('Theo mã, tên, số điện thoại')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Đối tác giao hàng/i })).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('haiz')).toBeInTheDocument();
    });
  });

  it('filters by search keyword', async () => {
    renderPage();
    const integratedTab = screen.getByRole('tab', { name: /Tích hợp/i });
    await userEvent.click(integratedTab);

    await waitFor(() => {
      expect(screen.getByText('GHTK')).toBeInTheDocument();
    });

    const search = screen.getByPlaceholderText('Theo mã, tên, số điện thoại');
    await userEvent.clear(search);
    await userEvent.type(search, 'GHTK');

    await waitFor(() => {
      expect(screen.getByText('GHTK')).toBeInTheDocument();
      expect(screen.queryByText('haiz')).not.toBeInTheDocument();
    });
  });

  it('toggles column visibility', async () => {
    renderPage();

    const toggleButton = screen.getByRole('button', { name: /Ẩn hiện cột/i });
    await userEvent.click(toggleButton);

    const dropdown = await waitFor(() => {
      const node = document.querySelector('.ant-dropdown');
      expect(node).toBeTruthy();
      return node;
    });
    const phoneOption = within(dropdown).getByText('Điện thoại');
    await userEvent.click(phoneOption);

    await waitFor(() => {
      expect(screen.queryByRole('columnheader', { name: 'Điện thoại' })).not.toBeInTheDocument();
    });
  });
});
