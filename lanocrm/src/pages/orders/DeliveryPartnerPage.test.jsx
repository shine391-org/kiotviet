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
  it('renders header actions', () => {
    renderPage();

    expect(screen.getByPlaceholderText('Theo mã, tên, số điện thoại')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Đối tác giao hàng/i })).toBeInTheDocument();
  });

  it('allows search input', async () => {
    renderPage();

    const search = screen.getByPlaceholderText('Theo mã, tên, số điện thoại');
    await userEvent.clear(search);
    await userEvent.type(search, 'test');

    expect(search.value).toBe('test');
  });

  it('renders toggle button', () => {
    renderPage();

    const toggleButton = screen.getByRole('button', { name: /Ẩn hiện cột/i });
    expect(toggleButton).toBeInTheDocument();
  });
});
