import React, { useState, useRef, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { Input, Button, Tooltip, Dropdown, Badge, AutoComplete } from 'antd';
import {
    SearchOutlined,
    PlusOutlined,
    CloseOutlined,
    UndoOutlined,
    SyncOutlined,
    UserOutlined,
    MenuOutlined,
    BarcodeOutlined,
    SwapOutlined,
    ShoppingCartOutlined,
    CaretDownOutlined,
    LockOutlined,
    PrinterOutlined,
    FileTextOutlined,
    GiftOutlined,
    ImportOutlined,
    EyeOutlined,
    QuestionCircleOutlined,
    AppstoreOutlined,
    LogoutOutlined,
    RollbackOutlined,
    SnippetsOutlined,
} from '@ant-design/icons';
import OrderProcessModal from './OrderProcessModal';
import ReturnInvoiceModal from './ReturnInvoiceModal';
import SyncDataModal from './SyncDataModal';
import PrintSettingsDropdown from './PrintSettingsDropdown';
import AddProductModal from './AddProductModal';
import DisplaySettingsModal from './DisplaySettingsModal';
import ShortcutsModal from './ShortcutsModal';
import posApi from '../../api/posApi';
import { logout } from '../../store/slices/authSlice';
import styles from './SalesHeader.module.css';

/**
 * Tab types:
 * - 'invoice': Hóa đơn (white background when active)
 * - 'order': Đặt hàng (orange background)
 */
const SalesHeader = ({
    tabs = [],
    activeTabId,
    onTabChange,
    onNewTab,
    onCloseTab,
    onSearch,
    onAddProduct,
}) => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const currentUser = useSelector((state) => state.auth?.user || state.user?.currentUser);
    const userName = currentUser?.name || currentUser?.full_name || currentUser?.username || 'Guest';
    const [searchText, setSearchText] = useState('');
    const [showOrderModal, setShowOrderModal] = useState(false);
    const [showReturnModal, setShowReturnModal] = useState(false);
    const [showSyncModal, setShowSyncModal] = useState(false);
    const [showAddProductModal, setShowAddProductModal] = useState(false);
    const [showDisplayModal, setShowDisplayModal] = useState(false);
    const [showShortcutsModal, setShowShortcutsModal] = useState(false);
    const [searchOptions, setSearchOptions] = useState([]);
    const searchInputRef = useRef(null);

    // F3 shortcut for search focus
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F3') {
                e.preventDefault();
                searchInputRef.current?.focus();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // Search products from API
    const searchProducts = useCallback(async (text) => {
        if (!text || text.length < 1) {
            setSearchOptions([]);
            return;
        }

        try {
            const response = await posApi.searchProducts({ search: text, limit: 5 });
            if (!response.success) return;

            // Filter out products with zero stock for POS
            const filtered = response.data
                .filter(p => (parseInt(p.stock_quantity) || 0) > 0)
                .map(p => ({
                    id: p.id,
                    code: p.code,
                    name: p.name,
                    variant: null,
                    price: parseFloat(p.selling_price) || 0,
                    stock: parseInt(p.stock_quantity) || 0,
                    ordered: parseInt(p.customer_ordered) || 0,
                    image: p.image || null,
                }));

            // Build options for AutoComplete
            const options = filtered.map(product => ({
                value: product.code,
                label: (
                    <div className={styles.productOption}>
                        <div className={styles.productImage}>
                            {product.image ? (
                                <img src={product.image} alt={product.name} />
                            ) : (
                                <div className={styles.imagePlaceholder} />
                            )}
                        </div>
                        <div className={styles.productInfo}>
                            <div className={styles.productName}>
                                {product.name}
                                {product.variant && <span className={styles.variant}>{product.variant}</span>}
                            </div>
                            {product.variants ? (
                                <div className={styles.variantsCount}>{product.variants} sản phẩm cùng loại</div>
                            ) : (
                                <>
                                    <div className={styles.productCode}>{product.code}</div>
                                    <div className={styles.productStock}>Tồn: {product.stock} | KH đặt: {product.ordered}</div>
                                </>
                            )}
                        </div>
                        <div className={styles.productPrice}>
                            {product.price.toLocaleString('vi-VN')}
                        </div>
                    </div>
                ),
                product,
            }));

            // Add "Thêm mới hàng hóa" option at the end
            options.push({
                value: '__add_new__',
                label: (
                    <div className={styles.addNewOption}>
                        <PlusOutlined /> Thêm mới hàng hóa
                    </div>
                ),
            });

            setSearchOptions(options);
        } catch (error) {
            console.error('Failed to search products:', error);
        }
    }, []);

    const handleSearchChange = (value) => {
        setSearchText(value);
        searchProducts(value);
        onSearch?.(value);
    };

    const handleProductSelect = (value, option) => {
        if (value === '__add_new__') {
            setShowAddProductModal(true);
        } else if (option.product) {
            onAddProduct?.(option.product);
            setSearchText('');
            setSearchOptions([]);
        }
    };

    const handleAddNewProduct = (productData) => {
        console.log('New product:', productData);
        // Product is already saved in AddProductModal
        onAddProduct?.(productData);
        setShowAddProductModal(false);
    };

    // Dropdown menu for adding new tab
    const addTabMenuItems = [
        {
            key: 'invoice',
            label: 'Thêm mới hóa đơn',
            icon: <SwapOutlined />,
            onClick: () => onNewTab('invoice'),
        },
        {
            key: 'order',
            label: 'Thêm mới đặt hàng',
            icon: <ShoppingCartOutlined />,
            onClick: () => onNewTab('order'),
        },
    ];

    // Burger menu items
    const burgerMenuItems = [
        {
            key: 'report',
            label: 'Xem báo cáo cuối ngày',
            icon: <FileTextOutlined />,
            onClick: () => navigate('/reports/daily'),
        },
        {
            key: 'process-order',
            label: 'Xử lý đặt hàng',
            icon: <ShoppingCartOutlined />,
            onClick: () => setShowOrderModal(true),
        },
        {
            key: 'return',
            label: 'Chọn hóa đơn trả hàng',
            icon: <RollbackOutlined />,
            onClick: () => setShowReturnModal(true),
        },
        {
            key: 'receipt',
            label: 'Lập phiếu thu',
            icon: <SnippetsOutlined />,
            onClick: () => navigate('/cash'),
        },
        {
            key: 'voucher',
            label: 'Phát hành voucher',
            icon: <GiftOutlined />,
            onClick: () => navigate('/customers/vouchers'),
        },
        {
            key: 'import',
            label: 'Import file',
            icon: <ImportOutlined />,
            disabled: true, // TODO: Implement import functionality
        },
        { type: 'divider' },
        {
            key: 'display',
            label: 'Tùy chọn hiển thị',
            icon: <EyeOutlined />,
            onClick: () => setShowDisplayModal(true),
        },
        {
            key: 'shortcuts',
            label: 'Phím tắt',
            icon: <QuestionCircleOutlined />,
            onClick: () => setShowShortcutsModal(true),
        },
        {
            key: 'admin',
            label: 'Quản lý',
            icon: <AppstoreOutlined />,
            onClick: () => navigate('/dashboard'),
        },
        { type: 'divider' },
        {
            key: 'logout',
            label: 'Đăng xuất',
            icon: <LogoutOutlined />,
            danger: true,
            onClick: () => {
                dispatch(logout());
                navigate('/login');
            },
        },
    ];

    const getTabIcon = (type) => {
        return type === 'order' ? <SwapOutlined /> : <SwapOutlined />;
    };

    const handleOrderSelect = (order) => {
        console.log('Selected order:', order);
        setShowOrderModal(false);
    };

    const handleReturnSelect = (invoice) => {
        console.log('Selected return invoice:', invoice);
        setShowReturnModal(false);
    };

    return (
        <>
            <header className={styles.header}>
                {/* Search Bar with AutoComplete */}
                <div className={styles.searchSection}>
                    <AutoComplete
                        value={searchText}
                        options={searchOptions}
                        onSearch={handleSearchChange}
                        onSelect={handleProductSelect}
                        className={styles.searchAutoComplete}
                        classNames={{ popup: { root: styles.searchDropdown } }}
                        notFoundContent={null}
                    >
                        <Input
                            ref={searchInputRef}
                            placeholder="Tìm hàng hóa (F3)"
                            prefix={<SearchOutlined />}
                            suffix={
                                <Tooltip title="Quét mã vạch">
                                    <BarcodeOutlined className={styles.barcodeIcon} />
                                </Tooltip>
                            }
                            className={styles.searchInput}
                            allowClear
                        />
                    </AutoComplete>
                </div>

                {/* Tabs Section */}
                <div className={styles.tabsSection}>
                    {tabs.map((tab) => (
                        <div
                            key={tab.id}
                            className={`
                                ${styles.tab} 
                                ${activeTabId === tab.id ? styles.tabActive : ''} 
                                ${tab.type === 'order' ? styles.tabOrder : styles.tabInvoice}
                            `}
                            onClick={() => onTabChange(tab.id)}
                        >
                            <span className={styles.tabIcon}>{getTabIcon(tab.type)}</span>
                            <span className={styles.tabLabel}>{tab.label}</span>
                            {tabs.length > 1 && (
                                <CloseOutlined
                                    className={styles.tabClose}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        onCloseTab(tab.id);
                                    }}
                                />
                            )}
                        </div>
                    ))}

                    {/* Add New Tab Button with Dropdown */}
                    <Dropdown
                        menu={{ items: addTabMenuItems }}
                        trigger={['click']}
                        placement="bottomRight"
                    >
                        <Button type="text" className={styles.addTabBtn}>
                            <PlusOutlined />
                            <CaretDownOutlined className={styles.addTabCaret} />
                        </Button>
                    </Dropdown>
                </div>

                {/* Action Buttons */}
                <div className={styles.actionsSection}>
                    <Tooltip title="Xử lý hàng đặt">
                        <Button
                            type="text"
                            icon={<LockOutlined />}
                            className={styles.actionBtn}
                            onClick={() => setShowOrderModal(true)}
                        />
                    </Tooltip>
                    <Tooltip title="Trả hàng">
                        <Button
                            type="text"
                            icon={<UndoOutlined />}
                            className={styles.actionBtn}
                            onClick={() => setShowReturnModal(true)}
                        />
                    </Tooltip>
                    <Tooltip title="Đồng bộ dữ liệu">
                        <Button
                            type="text"
                            icon={<SyncOutlined />}
                            className={styles.actionBtn}
                            onClick={() => setShowSyncModal(true)}
                        />
                    </Tooltip>

                    {/* Print Settings Dropdown */}
                    <PrintSettingsDropdown>
                        <Tooltip title="Thiết lập in">
                            <Button type="text" icon={<PrinterOutlined />} className={styles.actionBtn} />
                        </Tooltip>
                    </PrintSettingsDropdown>

                    {/* User & Menu */}
                    <Dropdown menu={{ items: burgerMenuItems }} trigger={['click']} placement="bottomRight">
                        <Button type="text" className={styles.userBtn}>
                            <span className={styles.userName}>{userName}</span>
                            <MenuOutlined />
                        </Button>
                    </Dropdown>
                </div>
            </header>

            {/* Modals */}
            <OrderProcessModal
                open={showOrderModal}
                onClose={() => setShowOrderModal(false)}
                onSelect={handleOrderSelect}
            />

            <ReturnInvoiceModal
                open={showReturnModal}
                onClose={() => setShowReturnModal(false)}
                onSelect={handleReturnSelect}
                onQuickReturn={() => console.log('Quick return')}
            />

            <SyncDataModal
                open={showSyncModal}
                onClose={() => setShowSyncModal(false)}
                onSyncAll={() => console.log('Sync all')}
            />

            <AddProductModal
                open={showAddProductModal}
                onClose={() => setShowAddProductModal(false)}
                onSave={handleAddNewProduct}
                initialName={searchText}
            />

            <DisplaySettingsModal
                open={showDisplayModal}
                onClose={() => setShowDisplayModal(false)}
            />

            <ShortcutsModal
                open={showShortcutsModal}
                onClose={() => setShowShortcutsModal(false)}
            />
        </>
    );
};

export default SalesHeader;
