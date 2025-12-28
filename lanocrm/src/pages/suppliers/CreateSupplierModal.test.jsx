
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';
import { vi } from 'vitest';
import CreateSupplierModal from './CreateSupplierModal';
import supplierApi from '../../api/supplierApi';

// Mock the supplierApi
vi.mock('../../api/supplierApi', () => ({
  default: {
    createSupplier: vi.fn(),
  },
}));

// Define mockMessage outside and use inside the mock, but since hoisting is the issue,
// we should define the mock factory content directly or use doMock.
// However, typically in Jest/Vitest, simple object literals work if they don't reference outside variables.
// The issue "Cannot access 'mockMessage' before initialization" happens because the factory is hoisted
// and tries to access `mockMessage` which is declared later (or initialized later).

// Correct approach: define the mock object inside the factory or use `vi.hoisted`.

const { mockMessage } = vi.hoisted(() => {
    return {
        mockMessage: {
            success: vi.fn(),
            error: vi.fn(),
        }
    }
});


// Use vi.mock to mock 'antd' and return a modified version of the real module
vi.mock('antd', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        message: mockMessage,
        Modal: ({ children, open, title, footer }) => (
            open ? (
                <div role="dialog" aria-modal="true" aria-label={title}>
                    <h1>{title}</h1>
                    {children}
                    {footer}
                </div>
            ) : null
        ),
    };
});

describe('CreateSupplierModal', () => {
  const defaultProps = {
    open: true,
    onCancel: vi.fn(),
    onSuccess: vi.fn(),
    supplierGroups: ['Group A', 'Group B'],
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders correctly when open', () => {
    render(<CreateSupplierModal {...defaultProps} />);
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Tạo nhà cung cấp')).toBeInTheDocument();
    expect(screen.getByLabelText(/Tên nhà cung cấp/i)).toBeInTheDocument();
  });

  it('validates required fields', async () => {
    render(<CreateSupplierModal {...defaultProps} />);

    // Find the submit button. The component renders footer with buttons.
    // "Lưu" is the text in the button.
    const submitButton = screen.getByText('Lưu');
    fireEvent.click(submitButton);

    // Expect validation error message
    // "Vui lòng nhập tên nhà cung cấp" is the message in rules.
    await waitFor(() => {
        expect(screen.getByText('Vui lòng nhập tên nhà cung cấp')).toBeInTheDocument();
    });

    expect(supplierApi.createSupplier).not.toHaveBeenCalled();
  });

  it('calls createSupplier API with correct values on successful submission', async () => {
    // Setup API mock success
    supplierApi.createSupplier.mockResolvedValue({ data: { id: 1, name: 'Test Supplier' } });

    render(<CreateSupplierModal {...defaultProps} />);

    // Fill in required fields
    fireEvent.change(screen.getByLabelText(/Tên nhà cung cấp/i), { target: { value: 'Test Supplier' } });

    // Fill other fields
    fireEvent.change(screen.getByLabelText(/Điện thoại/i), { target: { value: '0123456789' } });
    fireEvent.change(screen.getByLabelText(/Email/i), { target: { value: 'test@example.com' } });

    // Click submit
    const submitButton = screen.getByText('Lưu');
    fireEvent.click(submitButton);

    await waitFor(() => {
        expect(supplierApi.createSupplier).toHaveBeenCalledWith(expect.objectContaining({
            name: 'Test Supplier',
            phone: '0123456789',
            email: 'test@example.com',
        }));
    });

    await waitFor(() => {
        expect(mockMessage.success).toHaveBeenCalledWith('Tạo nhà cung cấp thành công');
        expect(defaultProps.onSuccess).toHaveBeenCalled();
        expect(defaultProps.onCancel).toHaveBeenCalled();
    });
  });

  it('handles API error correctly', async () => {
     // Setup API mock failure
     const errorMessage = 'Duplicate supplier name';
     supplierApi.createSupplier.mockRejectedValue({
         response: {
             data: {
                 message: errorMessage
             }
         }
     });

     render(<CreateSupplierModal {...defaultProps} />);

     // Fill in required fields
     fireEvent.change(screen.getByLabelText(/Tên nhà cung cấp/i), { target: { value: 'Test Supplier' } });

     // Click submit
     const submitButton = screen.getByText('Lưu');
     fireEvent.click(submitButton);

     await waitFor(() => {
         expect(supplierApi.createSupplier).toHaveBeenCalled();
     });

     await waitFor(() => {
         expect(mockMessage.error).toHaveBeenCalledWith(errorMessage);
     });

     expect(defaultProps.onSuccess).not.toHaveBeenCalled();
  });
});
