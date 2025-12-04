import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import TransferCreatePage from './TransferCreatePage';

describe('TransferCreatePage', () => {
  it('renders empty state and actions', () => {
    render(
      <MemoryRouter>
        <TransferCreatePage />
      </MemoryRouter>
    );

    expect(screen.getByText('Chuyển hàng')).toBeInTheDocument();
    expect(screen.getByText('Thêm sản phẩm từ file excel')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Chọn file dữ liệu/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Lưu tạm/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Hoàn thành/i })).toBeInTheDocument();
  });

  it('back button goes list route', () => {
    render(
      <MemoryRouter>
        <TransferCreatePage />
      </MemoryRouter>
    );
    const backBtn = screen.getByRole('button', { name: 'arrow-left' });
    fireEvent.click(backBtn);
  });
});
