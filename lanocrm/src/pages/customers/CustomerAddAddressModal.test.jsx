// src/pages/customers/CustomerAddAddressModal.test.jsx
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import CustomerAddAddressModal from './CustomerAddAddressModal';
import customerApi from '../../api/customerApi';
import { App } from 'antd';

// Mock API
vi.mock('../../api/customerApi');

// Mock matchMedia for Ant Design
window.matchMedia = window.matchMedia || function() {
  return {
    matches: false,
    addListener: function() {},
    removeListener: function() {}
  };
};

describe('CustomerAddAddressModal', () => {
  const mockCustomer = {
    id: 123,
    name: 'Test Customer',
    phone: '0987654321',
  };

  const onSuccessMock = vi.fn();
  const onCancelMock = vi.fn();

  const renderModal = (open = true) => {
    return render(
      <App>
        <CustomerAddAddressModal
          open={open}
          customer={mockCustomer}
          onSuccess={onSuccessMock}
          onCancel={onCancelMock}
        />
      </App>
    );
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders correctly when open', async () => {
    renderModal();

    expect(screen.getByText('Thêm địa chỉ nhận hàng')).toBeInTheDocument();
    expect(screen.getByLabelText('Tên gợi nhớ')).toBeInTheDocument();
    expect(screen.getByLabelText('Tên người nhận')).toBeInTheDocument();
    expect(screen.getByLabelText('Số điện thoại')).toBeInTheDocument();
    expect(screen.getByLabelText('Địa chỉ chi tiết')).toBeInTheDocument();
  });

  it('pre-fills fields from customer data', async () => {
    renderModal();

    await waitFor(() => {
        expect(screen.getByLabelText('Tên người nhận')).toHaveValue('Test Customer');
        expect(screen.getByLabelText('Số điện thoại')).toHaveValue('0987654321');
    });
  });

  it('calls API and onSuccess when form is submitted validly', async () => {
    customerApi.createAddress.mockResolvedValue({ success: true });
    renderModal();

    // Fill form
    fireEvent.change(screen.getByLabelText('Tên gợi nhớ'), { target: { value: 'Home' } });
    fireEvent.change(screen.getByLabelText('Địa chỉ chi tiết'), { target: { value: '123 Test St' } });

    // Check pre-filled values are there
    expect(screen.getByLabelText('Tên người nhận')).toHaveValue('Test Customer');

    // Submit
    fireEvent.click(screen.getByText('Lưu'));

    await waitFor(() => {
      expect(customerApi.createAddress).toHaveBeenCalledWith(123, {
        name: 'Home',
        recipient_name: 'Test Customer',
        phone: '0987654321',
        address: '123 Test St',
        province: undefined,
        ward: undefined
      });
      expect(onSuccessMock).toHaveBeenCalled();
      expect(onCancelMock).toHaveBeenCalled();
    });
  });

  it('shows validation error if required fields are missing', async () => {
    renderModal();

    // Clear required field
    fireEvent.change(screen.getByLabelText('Tên người nhận'), { target: { value: '' } });

    fireEvent.click(screen.getByText('Lưu'));

    await waitFor(() => {
      expect(screen.getByText('Vui lòng nhập tên người nhận')).toBeInTheDocument();
      expect(customerApi.createAddress).not.toHaveBeenCalled();
    });
  });

  it('handles API errors', async () => {
    const errorMsg = 'API Error';
    customerApi.createAddress.mockRejectedValue({ message: errorMsg });
    renderModal();

    fireEvent.change(screen.getByLabelText('Địa chỉ chi tiết'), { target: { value: '123 Test St' } });
    fireEvent.click(screen.getByText('Lưu'));

    await waitFor(() => {
      expect(customerApi.createAddress).toHaveBeenCalled();
      expect(onSuccessMock).not.toHaveBeenCalled();
      // Antd message should be shown (hard to test without mocking App/message,
      // but verifying onSuccess is not called is good enough for basic error handling check)
    });
  });
});
