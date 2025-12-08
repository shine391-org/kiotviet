import React, { useState, useEffect, useCallback } from 'react';
import { Input, Button, Select, Pagination, Empty, Spin } from 'antd';
import {
    SearchOutlined,
    AppstoreOutlined,
    UnorderedListOutlined,
    FilterOutlined,
    PictureOutlined,
} from '@ant-design/icons';
import posApi from '../../api/posApi';
import priceListApi from '../../api/priceListApi';
import styles from './ProductGrid.module.css';

const ProductGrid = ({ onAddProduct }) => {
    const [searchText, setSearchText] = useState('');
    const [viewMode, setViewMode] = useState('grid'); // 'grid' | 'list'
    const [currentPage, setCurrentPage] = useState(1);
    const [products, setProducts] = useState([]);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [priceLists, setPriceLists] = useState([]);
    const [selectedPriceList, setSelectedPriceList] = useState('default');
    const pageSize = 12;

    // Fetch products from API
    const fetchProducts = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await posApi.searchProducts({
                search: searchText,
                page: currentPage,
                limit: pageSize,
            });
            if (response.success) {
                const productList = response.data.map(p => ({
                    id: p.id,
                    sku: p.code,
                    name: p.name,
                    price: parseFloat(p.selling_price) || 0,
                    image: p.image || null,
                    stock: parseInt(p.stock_quantity) || 0,
                }));
                setProducts(productList);
                setTotal(response.pagination?.total || productList.length);
            } else {
                setError(response.message || 'Không thể tải sản phẩm');
            }
        } catch (error) {
            console.error('Failed to fetch products:', error);
            setError(error.response?.data?.message || 'Lỗi kết nối server');
        } finally {
            setLoading(false);
        }
    }, [searchText, currentPage]);

    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    // Fetch price lists
    useEffect(() => {
        const fetchPriceLists = async () => {
            try {
                const response = await priceListApi.getPriceLists({ is_active: 1, limit: 100 });
                if (response.success && response.data) {
                    const lists = response.data.map(p => ({
                        value: p.id,
                        label: p.name,
                    }));
                    setPriceLists([{ value: 'default', label: 'Bảng giá chung' }, ...lists]);
                }
            } catch (error) {
                console.error('Failed to fetch price lists:', error);
            }
        };
        fetchPriceLists();
    }, []);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            setCurrentPage(1);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchText]);

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
                    placeholder="Tìm sản phẩm..."
                    prefix={<SearchOutlined />}
                    value={searchText}
                    onChange={(e) => setSearchText(e.target.value)}
                    className={styles.searchInput}
                    allowClear
                />
                <Button icon={<FilterOutlined />} />
                <Select
                    value={selectedPriceList}
                    onChange={setSelectedPriceList}
                    className={styles.priceListSelect}
                    options={priceLists.length > 0 ? priceLists : [{ value: 'default', label: 'Bảng giá chung' }]}
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
                {loading ? (
                    <div className={styles.loadingContainer}><Spin /></div>
                ) : error ? (
                    <Empty
                        description={error}
                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                    />
                ) : products.length === 0 ? (
                    <Empty description="Không tìm thấy sản phẩm" />
                ) : (
                    products.map((product) => (
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
                    total={total}
                    onChange={setCurrentPage}
                    size="small"
                    showSizeChanger={false}
                />
            </div>
        </div>
    );
};

export default ProductGrid;
