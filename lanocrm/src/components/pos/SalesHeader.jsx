import React, { useState, useRef, useEffect } from 'react';
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
import styles from './SalesHeader.module.css';

// Mock product data for search
const MOCK_PRODUCTS = [
    { id: 1, code: 'KT010-D', name: 'Túi xách da đeo chéo 010', variant: 'D', price: 1450000, stock: 0, ordered: 0, image: null },
    { id: 2, code: 'KT101-NS', name: 'Túi đeo chéo nam công sở da bò đẳng cấp cho phái mạnh KT101', variant: 'NS', price: 1650000, stock: 0, ordered: 0, image: null },
    { id: 3, code: 'KT102', name: 'Túi đeo chéo nam da bò thật Lano thời trang cao cấp KT102', variant: null, price: 1650000, stock: 0, ordered: 0, image: null },
    { id: 4, code: 'KT103', name: 'Túi đeo chéo nam da thật Lano khỏe khoắn tiện lợi KT103', variant: null, price: 1450000, stock: 0, ordered: 0, image: null },
    { id: 5, code: 'KT104', name: 'Túi da nam đeo chéo Lano sang trọng lịch lãm KT104', variant: null, price: 1450000, stock: 0, ordered: 0, variants: 2, image: null },
    { id: 6, code: 'KT104-D', name: 'Túi da nam đeo chéo Lano sang trọng lịch lãm KT104', variant: 'D', price: 1450000, stock: 0, ordered: 0, image: null },
    { id: 7, code: 'SP00001', name: 'Túi xách nữ mini Lano thời trang', variant: null, price: 1250000, stock: 5, ordered: 0, image: null },
    { id: 8, code: 'SP00002', name: 'Túi đeo chéo nữ da thật Handmade', variant: null, price: 1750000, stock: 3, ordered: 2, image: null },
];

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
    const [searchText, setSearchText] = useState('');
    const [showOrderModal, setShowOrderModal] = useState(false);
    const [showReturnModal, setShowReturnModal] = useState(false);
    const [showSyncModal, setShowSyncModal] = useState(false);
    const [showAddProductModal, setShowAddProductModal] = useState(false);
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

    // Filter products based on search text
    const filterProducts = (text) => {
        if (!text || text.length < 1) {
            setSearchOptions([]);
            return;
        }

        const filtered = MOCK_PRODUCTS.filter(p =>
            p.name.toLowerCase().includes(text.toLowerCase()) ||
            p.code.toLowerCase().includes(text.toLowerCase())
        ).slice(0, 5);

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
    };

    const handleSearchChange = (value) => {
        setSearchText(value);
        filterProducts(value);
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
        // TODO: Save product to backend
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
        },
        {
            key: 'voucher',
            label: 'Phát hành voucher',
            icon: <GiftOutlined />,
        },
        {
            key: 'import',
            label: 'Import file',
            icon: <ImportOutlined />,
        },
        { type: 'divider' },
        {
            key: 'display',
            label: 'Tùy chọn hiển thị',
            icon: <EyeOutlined />,
        },
        {
            key: 'shortcuts',
            label: 'Phím tắt',
            icon: <QuestionCircleOutlined />,
        },
        {
            key: 'admin',
            label: 'Quản lý',
            icon: <AppstoreOutlined />,
        },
        { type: 'divider' },
        {
            key: 'logout',
            label: 'Đăng xuất',
            icon: <LogoutOutlined />,
            danger: true,
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
                        popupClassName={styles.searchDropdown}
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
                            <span className={styles.userName}>trung</span>
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
        </>
    );
};

export default SalesHeader;
