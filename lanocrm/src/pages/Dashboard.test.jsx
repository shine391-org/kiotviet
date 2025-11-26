import React from 'react';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { configureStore } from '@reduxjs/toolkit';
import Dashboard from './Dashboard';

const renderWithUser = (user) => {
  const store = configureStore({
    reducer: { auth: () => ({ user }) },
  });

  render(
    <Provider store={store}>
      <Dashboard />
    </Provider>
  );
};

describe('Dashboard Component', () => {
  it('renders a welcome message with the username', () => {
    renderWithUser({ username: 'TestUser' });
    expect(screen.getByText('Xin chào, TestUser! 👋')).toBeInTheDocument();
  });

  it('renders a generic welcome message if the user has no username', () => {
    renderWithUser(null);
    expect(screen.getByText('Xin chào, User! 👋')).toBeInTheDocument();
  });

  it('renders all stat cards', () => {
    renderWithUser(null);
    expect(screen.getByText('Doanh thu')).toBeInTheDocument();
    expect(screen.getByText('Đơn hàng')).toBeInTheDocument();
    expect(screen.getByText('Khách hàng')).toBeInTheDocument();
    expect(screen.getByText('Chi nhánh')).toBeInTheDocument();
  });
});
