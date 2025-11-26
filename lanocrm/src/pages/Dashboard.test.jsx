import React from 'react';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import configureStore from 'redux-mock-store';
import Dashboard from './Dashboard';

const mockStore = configureStore([]);

describe('Dashboard Component', () => {
  it('renders a welcome message with the username', () => {
    const store = mockStore({
      auth: { user: { username: 'TestUser' } },
    });

    render(
      <Provider store={store}>
        <Dashboard />
      </Provider>
    );

    expect(screen.getByText('Xin chào, TestUser! 👋')).toBeInTheDocument();
  });

  it('renders a generic welcome message if the user has no username', () => {
    const store = mockStore({
      auth: { user: null },
    });

    render(
      <Provider store={store}>
        <Dashboard />
      </Provider>
    );

    expect(screen.getByText('Xin chào, User! 👋')).toBeInTheDocument();
  });

  it('renders all stat cards', () => {
    const store = mockStore({
      auth: { user: null },
    });

    render(
      <Provider store={store}>
        <Dashboard />
      </Provider>
    );

    expect(screen.getByText('Doanh thu')).toBeInTheDocument();
    expect(screen.getByText('Đơn hàng')).toBeInTheDocument();
    expect(screen.getByText('Khách hàng')).toBeInTheDocument();
    expect(screen.getByText('Chi nhánh')).toBeInTheDocument();
  });
});
