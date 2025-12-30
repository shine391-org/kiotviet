import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ShortcutsModal from './ShortcutsModal';

// Mock matchMedia if not present (though setup file should handle it, just in case)
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(query => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(), // deprecated
        removeListener: vi.fn(), // deprecated
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

describe('ShortcutsModal', () => {
    it('renders correctly when open', () => {
        const handleClose = vi.fn();
        render(<ShortcutsModal open={true} onClose={handleClose} />);

        // Check title
        expect(screen.getByText('Danh sách phím tắt')).toBeInTheDocument();

        // Check section titles
        expect(screen.getAllByText('Chế độ bán hàng')).toHaveLength(1);
        expect(screen.getAllByText('Thao tác chung')).toHaveLength(1);

        // Check shortcuts
        expect(screen.getByText('F1')).toBeInTheDocument();
        expect(screen.getByText('Bán nhanh')).toBeInTheDocument();

        expect(screen.getByText('F2')).toBeInTheDocument();
        expect(screen.getByText('Bán thường')).toBeInTheDocument();

        expect(screen.getByText('F5')).toBeInTheDocument();
        expect(screen.getByText('Bán giao hàng')).toBeInTheDocument();

        expect(screen.getByText('F3')).toBeInTheDocument();
        expect(screen.getByText('Tìm hàng hóa')).toBeInTheDocument();

        expect(screen.getByText('F4')).toBeInTheDocument();
        expect(screen.getByText('Tìm khách hàng')).toBeInTheDocument();
    });

    it('does not render content when closed', () => {
        const handleClose = vi.fn();
        render(<ShortcutsModal open={false} onClose={handleClose} />);

        // Ant Design Modal portals might still exist in DOM but hidden or not rendered.
        // Usually queryByText returns null if not in document.
        expect(screen.queryByText('Danh sách phím tắt')).not.toBeInTheDocument();
    });
});
