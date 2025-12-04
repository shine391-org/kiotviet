import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import PermissionButton from './PermissionButton';

// Mock usePermission hook
vi.mock('../utils/usePermission', () => ({
    usePermission: vi.fn(),
}));

import { usePermission } from '../utils/usePermission';

describe('PermissionButton', () => {
    it('should render button when user has permission', () => {
        usePermission.mockReturnValue({
            hasPermission: vi.fn(() => true),
        });

        render(
            <PermissionButton permission="users.view">
                View Users
            </PermissionButton>
        );

        expect(screen.getByText('View Users')).toBeInTheDocument();
    });

    it('should not render button when user lacks permission', () => {
        usePermission.mockReturnValue({
            hasPermission: vi.fn(() => false),
        });

        render(
            <PermissionButton permission="users.delete">
                Delete User
            </PermissionButton>
        );

        expect(screen.queryByText('Delete User')).not.toBeInTheDocument();
    });

    it('should pass through button props', () => {
        usePermission.mockReturnValue({
            hasPermission: vi.fn(() => true),
        });

        render(
            <PermissionButton
                permission="users.edit"
                type="primary"
                danger
                data-testid="edit-button"
            >
                Edit
            </PermissionButton>
        );

        const button = screen.getByTestId('edit-button');
        expect(button).toBeInTheDocument();
        expect(button).toHaveClass('ant-btn-primary');
        expect(button).toHaveClass('ant-btn-dangerous');
    });

    it('should call hasPermission with correct permission', () => {
        const mockHasPermission = vi.fn(() => true);
        usePermission.mockReturnValue({
            hasPermission: mockHasPermission,
        });

        render(
            <PermissionButton permission="products.create">
                Create Product
            </PermissionButton>
        );

        expect(mockHasPermission).toHaveBeenCalledWith('products.create');
    });
});
