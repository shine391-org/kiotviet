import React from 'react';
import { render, screen } from '@testing-library/react';
import CashSummary from './CashSummary';

describe('CashSummary', () => {
  it('renders summary numbers', () => {
    render(
      <CashSummary
        summary={{ receipt: 1000000, payment: 200000, net: 800000, openingBalance: 500000 }}
        summaryLimited={false}
        loading={false}
      />
    );

    expect(screen.getAllByText(/Quỹ đầu kỳ/i)[0]).toBeInTheDocument();
    expect(screen.getByText(/1,000,000 đ/)).toBeInTheDocument();
    expect(screen.getByText(/200,000 đ/)).toBeInTheDocument();
  });

  it('shows limited badge when data is truncated', () => {
    render(
      <CashSummary
        summary={{ receipt: 0, payment: 0, net: 0, openingBalance: 0, limited: true }}
        summaryLimited
        loading={false}
      />
    );

    expect(screen.getAllByText(/≈ Ước tính/i).length).toBeGreaterThan(0);
  });
});
