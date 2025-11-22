import React from 'react';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import ProductTable from './ProductTable';
vi.mock('../../utils/usePermission', () => ({
  usePermission: () => ({ hasPermission: () => true }),
}));

// Helper render with minimal props
const renderTable = (products) => {
  render(
    <MemoryRouter>
      <ProductTable
        products={products}
        loading={false}
        pagination={{ page: 1, limit: 20, total: products.length }}
        productCategories={[]}
        expandedRowKeys={[]}
        setExpandedRowKeys={() => {}}
        onProductExpand={() => {}}
        handleTableChange={() => {}}
        handleDeleteProduct={() => {}}
        onCloneVariant={() => {}}
        onUndoDeleteVariant={() => {}}
        highlightedProductId={null}
        setHighlightedProductId={() => {}}
        onShowDeletedVariants={() => {}}
        deletedVariantsCount={{}}
      />
    </MemoryRouter>
  );
};

describe('ProductTable action column', () => {
  it('shows edit button when has_variants is string "0" (simple product)', () => {
    renderTable([{ id: 1, name: 'Product', code: 'P1', has_variants: '0' }]);
    const editBtn = screen.getByTitle(/chỉnh sửa sản phẩm/i);
    expect(editBtn).toBeInTheDocument();
  }, 10000);

  it('shows edit button when has_variants is string "1" (parent product)', () => {
    renderTable([{ id: 2, name: 'Parent', code: 'P2', has_variants: '1' }]);
    const editBtn = screen.getByTitle(/chỉnh sửa sản phẩm/i);
    expect(editBtn).toBeInTheDocument();
  });

  it('shows edit button when has_variants is undefined (fallback simple)', () => {
    renderTable([{ id: 3, name: 'Unknown', code: 'P3' }]);
    const editBtn = screen.getByTitle(/chỉnh sửa sản phẩm/i);
    expect(editBtn).toBeInTheDocument();
  });
});
