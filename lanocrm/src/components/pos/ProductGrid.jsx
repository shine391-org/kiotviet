import React, { useState } from 'react';
import { Input, Button, Select, Pagination, Empty } from 'antd';
import {
    SearchOutlined,
    AppstoreOutlined,
    UnorderedListOutlined,
    FilterOutlined,
    PictureOutlined,
} from '@ant-design/icons';
import styles from './ProductGrid.module.css';

// Mock product data
const MOCK_PRODUCTS = [
    { id: 1, sku: 'TJ001', name: 'Túi Jeep vải loại nhỏ', price: 550000, image: null },
    { id: 2, sku: 'MK001', name: 'Móc khóa da cá sấu', price: 50000, image: null },
    { id: 3, sku: 'TL001', name: 'Thắt lưng da cá sấu loại đặt', price: 1290000, image: null },
    { id: 4, sku: 'TX001', name: 'Túi xách nam giá rẻ KT38 nâu', price: 450000, image: null },
    { id: 5, sku: 'TX002', name: 'Túi xách nam giá rẻ KT38 đen', price: 450000, image: null },
    { id: 6, sku: 'TD001', name: 'Túi đeo chéo JEEP giá rẻ 001', price: 250000, image: null },
    { id: 7, sku: 'TX003', name: 'Túi xách da nam Polo cao cấp', price: 250000, image: null },
    { id: 8, sku: 'TJ002', name: 'Túi Da Nam Jeep 028', price: 1650000, image: null },
    { id: 9, sku: 'TD002', name: 'Túi xách da đeo chéo kt11', price: 1550000, image: null },
    { id: 10, sku: 'TD003', name: 'Túi đeo chéo nam da thật JEEP 01', price: 1450000, image: null },
    { id: 11, sku: 'LX001', name: 'Lọ xịt bảo dưỡng da', price: 120000, image: null },
    { id: 12, sku: 'TD004', name: 'Túi xách đeo chéo 010', price: 1450000, image: null },
];

const ProductGrid = ({ onAddProduct }) => {
    const [searchText, setSearchText] = useState('');
    const [viewMode, setViewMode] = useState('grid'); // 'grid' | 'list'
    const [currentPage, setCurrentPage] = useState(1);
    const pageSize = 12;

    // Filter products by search
    const filteredProducts = MOCK_PRODUCTS.filter(
        (p) =>
            p.name.toLowerCase().includes(searchText.toLowerCase()) ||
            p.sku.toLowerCase().includes(searchText.toLowerCase())
    );

    const paginatedProducts = filteredProducts.slice(
        (currentPage - 1) * pageSize,
        currentPage * pageSize
    );

    const handleProductClick = (product) => {
        onAddProduct({
            id: product.id,
            sku: product.sku,
            name: product.name,
            unitPrice: product.price,
        });
    };

    return (
        <div className={styles.productGrid}>
            {/* Header */}
            <div className={styles.header}>
                <Input
                    placeholder="Tìm khách hàng (F4)"
                    prefix={<SearchOutlined />}
                    value={searchText}
                    onChange={(e) => setSearchText(e.target.value)}
                    className={styles.searchInput}
                    allowClear
                />
                <Button icon={<FilterOutlined />} />
                <Select
                    defaultValue="default"
                    className={styles.priceListSelect}
                    options={[{ value: 'default', label: 'Bảng giá chung' }]}
                />
                <div className={styles.viewToggle}>
                    <Button
                        type={viewMode === 'list' ? 'primary' : 'text'}
                        icon={<UnorderedListOutlined />}
                        onClick={() => setViewMode('list')}
                    />
                    <Button
                        type={viewMode === 'grid' ? 'primary' : 'text'}
                        icon={<AppstoreOutlined />}
                        onClick={() => setViewMode('grid')}
                    />
                    <Button type="text" icon={<PictureOutlined />} />
                </div>
            </div>

            {/* Product Grid */}
            <div className={`${styles.gridContainer} ${viewMode === 'list' ? styles.listView : ''}`}>
                {paginatedProducts.length === 0 ? (
                    <Empty description="Không tìm thấy sản phẩm" />
                ) : (
                    paginatedProducts.map((product) => (
                        <div
                            key={product.id}
                            className={styles.productCard}
                            onClick={() => handleProductClick(product)}
                        >
                            <div className={styles.productImage}>
                                {product.image ? (
                                    <img src={product.image} alt={product.name} />
                                ) : (
                                    <div className={styles.imagePlaceholder}>
                                        <PictureOutlined />
                                    </div>
                                )}
                            </div>
                            <div className={styles.productInfo}>
                                <span className={styles.productName}>{product.name}</span>
                                <span className={styles.productPrice}>
                                    {product.price.toLocaleString('vi-VN')}
                                </span>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Pagination */}
            <div className={styles.pagination}>
                <Pagination
                    current={currentPage}
                    pageSize={pageSize}
                    total={filteredProducts.length}
                    onChange={setCurrentPage}
                    size="small"
                    showSizeChanger={false}
                />
            </div>
        </div>
    );
};

export default ProductGrid;
