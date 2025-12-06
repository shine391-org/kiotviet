import React, { useState, useRef, useEffect } from 'react';
import { Input, Button, Tooltip, Dropdown, Badge } from 'antd';
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
}) => {
    const [searchText, setSearchText] = useState('');
    const [showOrderModal, setShowOrderModal] = useState(false);
    const [showReturnModal, setShowReturnModal] = useState(false);
    const [showSyncModal, setShowSyncModal] = useState(false);
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

    const handleSearchChange = (e) => {
        setSearchText(e.target.value);
        onSearch?.(e.target.value);
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
                {/* Search Bar */}
                <div className={styles.searchSection}>
                    <Input
                        ref={searchInputRef}
                        placeholder="Tìm hàng hóa (F3)"
                        prefix={<SearchOutlined />}
                        suffix={
                            <Tooltip title="Quét mã vạch">
                                <BarcodeOutlined className={styles.barcodeIcon} />
                            </Tooltip>
                        }
                        value={searchText}
                        onChange={handleSearchChange}
                        className={styles.searchInput}
                        allowClear
                    />
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
                    <Tooltip title="Hoàn tác">
                        <Button type="text" icon={<UndoOutlined />} className={styles.actionBtn} />
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
        </>
    );
};

export default SalesHeader;
